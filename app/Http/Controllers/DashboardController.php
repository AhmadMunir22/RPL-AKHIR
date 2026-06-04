<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\Review;
use App\Models\User;
use App\Models\Voucher;
use App\Models\WalletTransaction;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Dashboard Home.
     */
    public function index()
    {
        $user = Auth::user();
        $recentBookings = Booking::with('court')
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->take(3)
            ->get();

        return view('dashboard.index', compact('user', 'recentBookings'));
    }

    /**
     * View Bookings History.
     */
    public function bookings()
    {
        $bookings = Booking::with('court')
            ->where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->paginate(5);

        return view('dashboard.bookings', compact('bookings'));
    }

    /**
     * View QR Pass Ticket.
     */
    public function ticket(int $id)
    {
        $booking = Booking::with('court')->findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        // Generate QR code (fallback to JS QR code in frontend if package absent)
        $qrSvg = '';
        try {
            if (class_exists('\\SimpleSoftwareIO\\QrCode\\Facades\\QrCode')) {
                $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($booking->qr_code ?? $booking->id);
            }
        } catch (\Throwable $e) {
            // silently fall back
        }

        return view('dashboard.ticket', compact('booking', 'qrSvg'));
    }

    /**
     * Submit Court Review.
     */
    public function submitReview(Request $request, int $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        // Validate that booking is completed or confirmed
        if (!in_array($booking->status, ['confirmed', 'completed'])) {
            return back()->withErrors(['error' => 'Ulasan hanya dapat dikirimkan untuk sesi yang sudah berjalan/dikonfirmasi.']);
        }

        // Save review
        Review::updateOrCreate(
            ['booking_id' => $booking->id, 'user_id' => Auth::id()],
            ['rating' => $request->rating, 'comment' => $request->comment]
        );

        // Recalculate Court rating average
        $court = $booking->court;
        $avg = Review::whereHas('booking', function ($q) use ($court) {
            $q->where('court_id', $court->id);
        })->avg('rating');

        $court->update(['rating_avg' => round($avg ?? 0.0, 2)]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ulasan dan rating Anda berhasil disimpan!'
            ]);
        }

        return back()->with('success', 'Ulasan dan rating Anda berhasil disimpan!');
    }

    /**
     * Wallet Screen.
     */
    public function wallet()
    {
        $transactions = WalletTransaction::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('dashboard.wallet', compact('transactions'));
    }

    /**
     * Top-up Wallet.
     */
    public function topUp(Request $request, PaymentService $paymentService)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
        ]);

        $paymentService->topUpWallet(Auth::id(), $request->amount, 'DEP-' . time());

        return back()->with('success', 'Top-up saldo dompet digital senilai Rp ' . number_format($request->amount, 0, ',', '.') . ' berhasil!');
    }

    /**
     * Loyalty points ledger page.
     */
    public function loyalty()
    {
        $points = LoyaltyPoint::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        // Get redeemeable vouchers: cost 50 loyalty points to redeem a 25K voucher!
        return view('dashboard.loyalty', compact('points'));
    }

    /**
     * Redeem Voucher with points.
     */
    public function redeemVoucher(Request $request)
    {
        $user = User::findOrFail(Auth::id());
        
        if ($user->points < 10) {
            return back()->withErrors(['points' => 'Poin Anda tidak mencukupi untuk penukaran voucher (minimal 10 poin).']);
        }

        // Deduct points
        $user->decrement('points', 10);

        // Record loyalty transaction
        LoyaltyPoint::create([
            'user_id' => $user->id,
            'points' => -10,
            'type' => 'redeem',
            'description' => 'Redeem Main Gratis 1 Jam'
        ]);

        // Generate voucher code
        $code = 'GRATIS-' . strtoupper(Str::random(6));
        Voucher::create([
            'code' => $code,
            'type' => 'free_hour',
            'value' => 0, // Dihitung otomatis di backend berdasarkan harga lapangan per jam
            'min_booking' => 0,
            'quota' => 1,
            'expired_at' => now()->addMonth(),
        ]);

        return back()->with('success', "Penukaran berhasil! Gunakan kode voucher berikut saat checkout untuk main gratis 1 jam: **{$code}**");
    }

    // --- Profile Editing ---
    public function profile()
    {
        return view('dashboard.profile', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone,' . $user->id,
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
        ];

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        $user->update($data);

        return back()->with('success', 'Profil Anda berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::findOrFail(Auth::id());

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak cocok.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password Anda berhasil diubah!');
    }
}
