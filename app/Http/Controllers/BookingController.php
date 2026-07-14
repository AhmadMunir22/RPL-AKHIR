<?php

namespace App\Http\Controllers;

use App\Actions\CreateBookingAction;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Voucher;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Class BookingController
 * 
 * Mengelola alur reservasi lapangan padel, mulai dari halaman checkout,
 * proses reservasi slot, pengecekan validitas voucher promo, halaman pembayaran,
 * hingga endpoint API berbasis Sanctum untuk aplikasi mobile/frontend eksternal.
 * 
 * @package App\Http\Controllers
 */
class BookingController extends Controller
{
    /**
     * Service untuk mengelola logika ketersediaan dan kalkulasi booking.
     */
    protected BookingService $bookingService;

    /**
     * Action untuk menangani pembuatan record reservasi secara transaksional & aman.
     */
    protected CreateBookingAction $createBookingAction;

    /**
     * BookingController constructor.
     * 
     * @param BookingService $bookingService
     * @param CreateBookingAction $createBookingAction
     */
    public function __construct(BookingService $bookingService, CreateBookingAction $createBookingAction)
    {
        $this->bookingService = $bookingService;
        $this->createBookingAction = $createBookingAction;
    }

    /**
     * Menampilkan Halaman Checkout.
     * 
     * Memvalidasi parameter checkout, memeriksa kelengkapan profil WhatsApp pengguna,
     * memastikan slot yang dipilih masih tersedia (tanpa cache), menghitung harga total,
     * dan menampilkan halaman checkout reservasi.
     * 
     * @param Request $request
     * @return RedirectResponse|View
     */
    public function checkoutPage(Request $request): RedirectResponse|View
    {
        // 1. Validasi input request checkout
        $request->validate([
            'court_id' => 'required|exists:courts,id',
            'date'     => 'required|date|after_or_equal:today',
            'slots'    => 'required|array|min:1',
            'slots.*'  => 'distinct|string',
        ]);

        // 2. Proteksi WA: Pengguna wajib mengisi nomor WhatsApp di profil terlebih dahulu demi alur notifikasi tiket.
        if (empty(Auth::user()->phone)) {
            return redirect()->route('dashboard.profile')
                ->with('error', 'Anda harus melengkapi Nomor WhatsApp terlebih dahulu sebelum melakukan reservasi lapangan.');
        }

        $court = Court::findOrFail($request->court_id);
        $date  = $request->date;
        // Normalisasi slot agar unik dan teratur indeksnya
        $slots = array_values(array_unique($request->slots));

        // 3. Pastikan slot masih tersedia (diperiksa secara real-time tanpa cache)
        try {
            $this->bookingService->assertSlotsAvailable($court->id, $date, $slots);
        } catch (\Exception $e) {
            // Jika ada slot yang sudah dipesan pengguna lain, kembalikan ke detail lapangan dengan pesan error
            return redirect()
                ->route('courts.show', $court->id)
                ->withErrors(['booking' => $e->getMessage()])
                ->withInput();
        }

        // 4. Hitung rincian harga (subtotal, diskon voucher, total harga, dan DP 50%)
        $pricing = $this->bookingService->calculatePricing($court->id, $slots, $request->voucher_code);

        // 5. Tampilkan halaman checkout beserta data pendukung
        return view('booking.checkout', compact('court', 'date', 'slots', 'pricing'));
    }

    /**
     * Melakukan Reservasi Slot Lapangan.
     * 
     * Menyimpan data reservasi sementara dan mengalihkan pengguna ke halaman pembayaran.
     * Mendukung auto-confirm jika harga akhir Rp 0 karena diskon penuh voucher.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function reserve(Request $request): RedirectResponse
    {
        // 1. Normalisasi kode voucher menjadi huruf besar (case-insensitive) sebelum divalidasi
        if ($request->filled('voucher_code')) {
            $request->merge(['voucher_code' => strtoupper(trim($request->voucher_code))]);
        }

        // 2. Validasi parameter input form reservasi
        $request->validate([
            'court_id'     => 'required|exists:courts,id',
            'date'         => 'required|date|after_or_equal:today',
            'slots'        => 'required|array|min:1',
            'slots.*'      => 'distinct|string',
            'voucher_code' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (!$value) return;
                    // Pastikan voucher aktif dan ada di DB
                    $exists = Voucher::where('code', $value)->exists();
                    if (!$exists) {
                        $fail('Kode voucher tidak ditemukan atau sudah tidak berlaku.');
                    }
                },
            ],
            'notes'        => 'nullable|string|max:500',
        ]);

        try {
            // 3. Eksekusi pembuatan reservasi secara transaksional
            $booking = $this->createBookingAction->execute(
                Auth::id(),
                $request->court_id,
                $request->date,
                $request->slots,
                $request->voucher_code ?: null,
                $request->notes
            );

            // 4. AUTO-CONFIRM: Jika total harga Rp 0 (misalnya voucher free hour/diskon 100%), langsung konfirmasi lunas.
            if ($booking->total_price <= 0) {
                app(\App\Actions\ProcessPaymentAction::class)->execute(
                    $booking->id,
                    'voucher',
                    'FREE-' . time(),
                    0,
                    ['paid_via' => 'voucher_full']
                );
                return redirect()->route('dashboard.bookings')->with('success', 'Booking gratis berhasil menggunakan poin/voucher!');
            }

            // 5. Alihkan ke halaman pembayaran jika ada biaya yang harus dibayar
            return redirect()->route('booking.pay', $booking->id);
        } catch (\Exception $e) {
            // Kembalikan ke halaman sebelumnya jika terjadi kendala/double booking
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Menampilkan Halaman Pembayaran.
     * 
     * Menyajikan opsi pembayaran (Wallet, Midtrans Payment Gateway, Transfer Bank) untuk booking terkait.
     * 
     * @param int $id ID dari Booking
     * @return RedirectResponse|View
     */
    public function paymentPage(int $id): RedirectResponse|View
    {
        // 1. Ambil data booking beserta informasi lapangannya
        $booking = Booking::with(['court'])->findOrFail($id);

        // 2. Proteksi Otorisasi: Pastikan pemesan adalah user yang sedang login
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        // 3. Jika booking sudah lunas, langsung arahkan ke dashboard bookings
        if ($booking->payment_status === 'paid') {
            return redirect()->route('dashboard.bookings')->with('info', 'Booking ini sudah lunas.');
        }

        // 4. Tampilkan halaman pembayaran
        return view('booking.pay', compact('booking'));
    }

    /**
     * Memeriksa Validitas Voucher via AJAX.
     * 
     * Mengembalikan data respons JSON yang berisi diskon dan total harga jika voucher valid,
     * atau alasan spesifik mengapa voucher tidak dapat digunakan.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkVoucher(Request $request): JsonResponse
    {
        // 1. Validasi input request AJAX
        $request->validate([
            'court_id' => 'required|exists:courts,id',
            'slots'    => 'required|array|min:1',
            'code'     => 'required|string|max:50',
        ]);

        $code    = strtoupper(trim($request->code));
        $voucher = Voucher::where('code', $code)->first();

        // 2. Jika kode voucher tidak ditemukan di database
        if (!$voucher) {
            return response()->json([
                'valid'   => false,
                'message' => 'Kode voucher tidak ditemukan.',
            ], 404);
        }

        // 3. Gunakan kalkulator harga dari BookingService untuk menghitung efek potongan harga
        $pricing = $this->bookingService->calculatePricing(
            (int) $request->court_id,
            $request->slots,
            $code
        );

        // 4. Jika diskon lebih besar dari 0, berarti voucher berhasil diterapkan
        if ($pricing['discount'] > 0) {
            return response()->json([
                'valid'    => true,
                'discount' => $pricing['discount'],
                'total'    => $pricing['total_price'],
                'message'  => 'Voucher berhasil diterapkan!',
            ]);
        }

        // 5. Analisis penyebab kegagalan penerapan voucher untuk dikembalikan sebagai error detail
        if ($voucher->quota <= 0) {
            $reason = 'Quota voucher sudah habis.';
        } elseif ($voucher->expired_at && $voucher->expired_at->isPast()) {
            $reason = 'Voucher sudah kadaluarsa.';
        } elseif ($pricing['subtotal'] < $voucher->min_booking) {
            $reason = 'Total belanja minimal Rp ' . number_format($voucher->min_booking, 0, ',', '.') . ' untuk menggunakan voucher ini.';
        } else {
            $reason = 'Voucher tidak valid untuk pesanan ini.';
        }

        return response()->json(['valid' => false, 'message' => $reason], 422);
    }

    // --- Sanctum REST API Endpoints (Untuk Integrasi Mobile/External) ---

    /**
     * Endpoint API untuk Reservasi Lapangan.
     * 
     * Digunakan oleh client API untuk membuat reservasi dengan respon format JSON.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function apiReserve(Request $request): JsonResponse
    {
        // 1. Validasi input request API
        $request->validate([
            'court_id'     => 'required|exists:courts,id',
            'date'         => 'required|date|after_or_equal:today',
            'slots'        => 'required|array|min:1',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
            'notes'        => 'nullable|string|max:500',
        ]);

        try {
            // 2. Eksekusi pembuatan reservasi secara transaksional
            $booking = $this->createBookingAction->execute(
                Auth::id(),
                $request->court_id,
                $request->date,
                $request->slots,
                $request->voucher_code,
                $request->notes
            );

            // 3. Return JSON sukses beserta info pembayaran
            return response()->json([
                'success' => true,
                'message' => 'Reservasi berhasil dibuat. Silakan lakukan pembayaran.',
                'data'    => [
                    'booking_id'  => $booking->id,
                    'total_price' => $booking->total_price,
                    'qr_code'     => $booking->qr_code,
                ]
            ], 201);
        } catch (\Exception $e) {
            // 4. Return JSON gagal jika terjadi error
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Endpoint API untuk melihat riwayat reservasi user yang sedang login.
     * 
     * @return JsonResponse
     */
    public function apiUserBookings(): JsonResponse
    {
        // Ambil riwayat booking user secara berurutan berdasarkan tanggal terbaru
        $bookings = Booking::with('court')
            ->where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $bookings
        ]);
    }
}

