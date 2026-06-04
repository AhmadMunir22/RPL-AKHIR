<?php

namespace App\Http\Controllers;

use App\Actions\CreateBookingAction;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Voucher;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    protected BookingService $bookingService;
    protected CreateBookingAction $createBookingAction;

    public function __construct(BookingService $bookingService, CreateBookingAction $createBookingAction)
    {
        $this->bookingService = $bookingService;
        $this->createBookingAction = $createBookingAction;
    }

    /**
     * Show Checkout Page.
     */
    public function checkoutPage(Request $request)
    {
        $request->validate([
            'court_id' => 'required|exists:courts,id',
            'date' => 'required|date|after_or_equal:today',
            'slots' => 'required|array|min:1',
            'slots.*' => 'distinct|string',
        ]);

        $court = Court::findOrFail($request->court_id);
        $date = $request->date;
        $slots = array_values(array_unique($request->slots));

        try {
            $this->bookingService->assertSlotsAvailable($court->id, $date, $slots);
        } catch (\Exception $e) {
            return redirect()
                ->route('courts.show', $court->id)
                ->withErrors(['booking' => $e->getMessage()])
                ->withInput();
        }

        $pricing = $this->bookingService->calculatePricing($court->id, $slots, $request->voucher_code);

        return view('booking.checkout', compact('court', 'date', 'slots', 'pricing'));
    }

    /**
     * Reserve Slots and redirect to payment page.
     */
    public function reserve(Request $request)
    {
        // Normalize voucher code to uppercase before validation
        if ($request->filled('voucher_code')) {
            $request->merge(['voucher_code' => strtoupper(trim($request->voucher_code))]);
        }

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
                    $exists = \App\Models\Voucher::where('code', $value)->exists();
                    if (!$exists) {
                        $fail('Kode voucher tidak ditemukan atau sudah tidak berlaku.');
                    }
                },
            ],
            'notes'        => 'nullable|string|max:500',
        ]);

        try {
            $booking = $this->createBookingAction->execute(
                Auth::id(),
                $request->court_id,
                $request->date,
                $request->slots,
                $request->voucher_code ?: null,
                $request->notes
            );

            // Auto-confirm if total price is 0 (due to voucher)
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

            return redirect()->route('booking.pay', $booking->id);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Show Payment Page.
     */
    public function paymentPage(int $id)
    {
        $booking = Booking::with(['court'])->findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        if ($booking->payment_status === 'paid') {
            return redirect()->route('dashboard.bookings')->with('info', 'Booking ini sudah lunas.');
        }

        return view('booking.pay', compact('booking'));
    }

    /**
     * Check voucher validity via AJAX and return JSON response.
     */
    public function checkVoucher(Request $request)
    {
        $request->validate([
            'court_id' => 'required|exists:courts,id',
            'slots'    => 'required|array|min:1',
            'code'     => 'required|string|max:50',
        ]);

        $code    = strtoupper(trim($request->code));
        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            return response()->json([
                'valid'   => false,
                'message' => 'Kode voucher tidak ditemukan.',
            ], 404);
        }

        // Use calculatePricing to get discount (Eloquent scope excludes soft-deleted)
        $pricing = $this->bookingService->calculatePricing(
            (int) $request->court_id,
            $request->slots,
            $code
        );

        if ($pricing['discount'] > 0) {
            return response()->json([
                'valid'    => true,
                'discount' => $pricing['discount'],
                'total'    => $pricing['total_price'],
                'message'  => 'Voucher berhasil diterapkan!',
            ]);
        }

        // Determine exact reason the voucher is not applicable
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

    // --- Sanctum REST API Endpoints ---

    public function apiReserve(Request $request)
    {
        $request->validate([
            'court_id' => 'required|exists:courts,id',
            'date' => 'required|date|after_or_equal:today',
            'slots' => 'required|array|min:1',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $booking = $this->createBookingAction->execute(
                Auth::id(),
                $request->court_id,
                $request->date,
                $request->slots,
                $request->voucher_code,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Reservasi berhasil dibuat. Silakan lakukan pembayaran.',
                'data' => [
                    'booking_id' => $booking->id,
                    'total_price' => $booking->total_price,
                    'qr_code' => $booking->qr_code,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function apiUserBookings()
    {
        $bookings = Booking::with('court')
            ->where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }
}
