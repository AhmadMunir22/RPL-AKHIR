<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\Review;
use App\Models\User;
use App\Models\Voucher;
use App\Models\WalletTransaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Class DashboardController
 * 
 * Mengelola area personal pengguna (Member Dashboard) meliputi ringkasan dashboard,
 * riwayat pemesanan lapangan, unduh tiket digital dengan QR code generator,
 * pengiriman ulasan lapangan, mutasi saldo wallet digital (top-up & transaksi),
 * serta penukaran loyalty points menjadi voucher gratis 1 jam.
 * 
 * @package App\Http\Controllers
 */
class DashboardController extends Controller
{
    /**
     * Tampilan Utama Dashboard Pengguna.
     * 
     * @return View
     */
    public function index(): View
    {
        $user = Auth::user();
        
        // Ambil 3 booking terakhir milik user
        $recentBookings = Booking::with('court')
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->take(3)
            ->get();

        return view('dashboard.index', compact('user', 'recentBookings'));
    }

    /**
     * Menampilkan Riwayat Semua Pemesanan/Booking Pengguna.
     * 
     * @return View
     */
    public function bookings(): View
    {
        $bookings = Booking::with('court')
            ->where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->paginate(5); // Paginasi 5 item per halaman

        return view('dashboard.bookings', compact('bookings'));
    }

    /**
     * Menampilkan Tiket Digital & QR Code Sesi Lapangan.
     * 
     * @param int $id ID dari Booking
     * @return View
     */
    public function ticket(int $id): View
    {
        $booking = Booking::with('court')->findOrFail($id);

        // Proteksi Otorisasi: Hanya pemesan asli yang bisa melihat tiketnya
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        // Jalankan library QrCode Laravel jika terpasang untuk merender SVG QR Code
        $qrSvg = '';
        try {
            if (class_exists('\\SimpleSoftwareIO\\QrCode\\Facades\\QrCode')) {
                $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($booking->qr_code ?? $booking->id);
            }
        } catch (\Throwable $e) {
            // Silently fallback: Jika library absen, render QR Code di sisi client via JavaScript
        }

        return view('dashboard.ticket', compact('booking', 'qrSvg'));
    }

    /**
     * Mengirimkan Ulasan & Rating Lapangan Padel.
     * 
     * @param Request $request
     * @param int $id ID dari Booking
     * @return JsonResponse|RedirectResponse
     */
    public function submitReview(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        // Validasi status: ulasan hanya bisa dikirimkan untuk sesi confirmed/completed
        if (!in_array($booking->status, ['confirmed', 'completed'])) {
            return back()->withErrors(['error' => 'Ulasan hanya dapat dikirimkan untuk sesi yang sudah berjalan/dikonfirmasi.']);
        }

        // Buat atau perbarui ulasan (satu booking hanya boleh memiliki satu ulasan)
        Review::updateOrCreate(
            ['booking_id' => $booking->id, 'user_id' => Auth::id()],
            ['rating'     => $request->rating, 'comment' => $request->comment]
        );

        // Hitung ulang rata-rata rating lapangan terkait secara presisi
        $court = $booking->court;
        $avg = Review::whereHas('booking', function ($q) use ($court) {
            $q->where('court_id', $court->id);
        })->avg('rating');

        $court->update(['rating_avg' => round($avg ?? 0.0, 2)]);

        // Kembalikan JSON jika diminta secara asinkronus (AJAX)
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ulasan dan rating Anda berhasil disimpan!'
            ]);
        }

        return back()->with('success', 'Ulasan dan rating Anda berhasil disimpan!');
    }

    /**
     * Menampilkan Halaman Wallet Digital & Riwayat Transaksi.
     * 
     * @return View
     */
    public function wallet(): View
    {
        $transactions = WalletTransaction::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('dashboard.wallet', compact('transactions'));
    }

    /**
     * Memproses Top-up Saldo Wallet Digital.
     * 
     * @param Request $request
     * @param PaymentService $paymentService
     * @return RedirectResponse
     */
    public function topUp(Request $request, PaymentService $paymentService): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000', // minimal top-up Rp 10.000
        ]);

        $paymentService->topUpWallet(Auth::id(), $request->amount, 'DEP-' . time());

        return back()->with('success', 'Top-up saldo dompet digital senilai Rp ' . number_format($request->amount, 0, ',', '.') . ' berhasil!');
    }

    /**
     * Menampilkan Halaman Riwayat Poin Loyalty Reward.
     * 
     * @return View
     */
    public function loyalty(): View
    {
        $points = LoyaltyPoint::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard.loyalty', compact('points'));
    }

    /**
     * Menukarkan Poin Loyalty Menjadi Voucher Gratis 1 Jam.
     * 
     * Menukarkan 10 poin loyalty yang didapat untuk memperoleh voucher bertipe `free_hour`.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function redeemVoucher(Request $request): RedirectResponse
    {
        $user = User::findOrFail(Auth::id());
        
        // Validasi kelayakan jumlah poin
        if ($user->points < 10) {
            return back()->withErrors(['points' => 'Poin Anda tidak mencukupi untuk penukaran voucher (minimal 10 poin).']);
        }

        // 1. Kurangi poin loyalty user
        $user->decrement('points', 10);

        // 2. Catat transaksi mutasi pengurangan poin
        LoyaltyPoint::create([
            'user_id'     => $user->id,
            'points'      => -10,
            'type'        => 'redeem',
            'description' => 'Redeem Main Gratis 1 Jam'
        ]);

        // 3. Buat record voucher promo baru yang siap dipakai saat checkout
        $code = 'GRATIS-' . strtoupper(Str::random(6));
        Voucher::create([
            'code'        => $code,
            'type'        => 'free_hour',
            'value'       => 0, // dihitung otomatis gratis 1 jam pada saat checkout berdasarkan harga lapangan terkait
            'min_booking' => 0,
            'quota'       => 1,
            'expired_at'  => now()->addMonth(), // voucher berlaku selama 1 bulan sejak penukaran
        ]);

        return back()->with('success', "Penukaran berhasil! Gunakan kode voucher berikut saat checkout untuk main gratis 1 jam: **{$code}**");
    }

    /**
     * Menampilkan Form Profil Akun Pengguna.
     * 
     * @return View
     */
    public function profile(): View
    {
        return view('dashboard.profile', ['user' => Auth::user()]);
    }

    /**
     * Memperbarui Informasi Profil Pengguna (Nama, No HP/WA, Foto Profil).
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = User::findOrFail(Auth::id());

        $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'required|string|unique:users,phone,' . $user->id,
            'avatar' => 'nullable|image|max:2048', // limit foto profil maksimal 2MB
        ]);

        $data = [
            'name'  => $request->name,
            'phone' => $request->phone,
        ];

        // Simpan avatar jika ada berkas foto profil yang diunggah
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        $user->update($data);

        return back()->with('success', 'Profil Anda berhasil diperbarui!');
    }

    /**
     * Memperbarui Password Pengguna dari Dashboard.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = User::findOrFail(Auth::id());

        // Pastikan password lama cocok
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak cocok.']);
        }

        // Update password baru dengan enkripsi Hash
        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password Anda berhasil diubah!');
    }
}

