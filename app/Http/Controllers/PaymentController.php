<?php

namespace App\Http\Controllers;

use App\Actions\ProcessPaymentAction;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected ProcessPaymentAction $processPaymentAction;

    public function __construct(PaymentService $paymentService, ProcessPaymentAction $processPaymentAction)
    {
        $this->paymentService = $paymentService;
        $this->processPaymentAction = $processPaymentAction;
    }

    /**
     * Process checkout pay using Wallet balance.
     */
    public function payWithWallet(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'pay_amount' => 'required|numeric|min:0',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $this->paymentService->processWalletPayment($booking, $request->pay_amount);
            
            // Confirm the booking
            $this->processPaymentAction->execute(
                $booking->id,
                'wallet',
                'WAL-' . $booking->id . '-' . time(),
                $request->pay_amount,
                ['paid_via' => 'wallet']
            );

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diselesaikan menggunakan Dompet Digital!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }



    /**
     * Get DOKU Payment URL for booking.
     */
    public function payWithDoku(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'pay_amount' => 'required|numeric|min:0',
            'payment_type' => 'nullable|string',
        ]);

        $booking = Booking::with('user')->findOrFail($request->booking_id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $url = $this->paymentService->getDokuCheckoutUrl($booking, $request->pay_amount, $request->payment_type);

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Mock payment success for testing when real keys are not set.
     */
    public function payMock(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'pay_amount' => 'required|numeric|min:0',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        $this->processPaymentAction->execute(
            $booking->id,
            'doku_mock',
            'MOCK-TXN-' . $booking->id . '-' . time(),
            $request->pay_amount,
            ['mock_payment' => true],
            'settlement'
        );

        return response()->json([
            'success' => true,
            'message' => 'Simulasi pembayaran berhasil!'
        ]);
    }

    /**
     * Upload manual bank transfer receipt.
     */
    public function uploadReceipt(Request $request, int $id)
    {
        $request->validate([
            'receipt' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('receipts', 'public');
            $booking->update([
                'payment_receipt' => $path,
                'payment_status' => 'awaiting_approval',
                'payment_method' => 'bank_transfer',
            ]);
        }

        return redirect()->route('dashboard.bookings')->with('success', 'Bukti transfer berhasil diunggah. Menunggu persetujuan Admin.');
    }

    /**
     * Handle incoming DOKU Webhook (Notification).
     */
    public function dokuCallback(Request $request)
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];

        Log::info('DOKU Webhook Received', [
            'headers' => [
                'Client-Id'         => $request->header('Client-Id'),
                'Request-Id'        => $request->header('Request-Id'),
                'Request-Timestamp' => $request->header('Request-Timestamp'),
                'Signature'         => $request->header('Signature'),
            ],
            'payload' => $payload,
        ]);

        // --- Validasi Tanda Tangan (Signature) DOKU ---
        $clientId         = $request->header('Client-Id');
        $requestId        = $request->header('Request-Id');
        $requestTimestamp = $request->header('Request-Timestamp');
        $signatureHeader  = $request->header('Signature');

        if (!$clientId || !$requestId || !$requestTimestamp || !$signatureHeader) {
            Log::warning('DOKU Webhook: Missing required headers.');
            return response()->json(['status' => 'bad_request'], 400);
        }

        $secretKey  = config('services.doku.secret_key');
        $targetPath = $request->getPathInfo();

        $digest = base64_encode(hash('sha256', $rawBody, true));

        $signatureComponent = "Client-Id:" . $clientId . "\n"
            . "Request-Id:" . $requestId . "\n"
            . "Request-Timestamp:" . $requestTimestamp . "\n"
            . "Request-Target:" . $targetPath . "\n"
            . "Digest:" . $digest;

        $calculatedSignature = "HMACSHA256=" . base64_encode(
            hash_hmac('sha256', $signatureComponent, $secretKey, true)
        );

        if (!hash_equals($calculatedSignature, $signatureHeader)) {
            Log::error('DOKU Webhook: Invalid Signature.', [
                'calculated' => $calculatedSignature,
                'received'   => $signatureHeader,
            ]);
            return response()->json(['status' => 'unauthorized'], 401);
        }

        // --- Signature valid, proses payload ---
        $invoiceNumber     = $payload['order']['invoice_number'] ?? null;
        $transactionStatus = $payload['transaction']['status']   ?? null;

        if (!$invoiceNumber || !$transactionStatus) {
            Log::warning('DOKU Webhook: Missing invoice_number or transaction status.', $payload);
            return response()->json(['status' => 'ignored']);
        }

        // Format invoice_number: "BOOK-{booking_id}-{timestamp}"
        // Contoh: "BOOK-42-1750123456"  => parts = ['BOOK', '42', '1750123456']
        $parts = explode('-', $invoiceNumber);
        if (count($parts) < 2 || strtoupper($parts[0]) !== 'BOOK') {
            Log::warning('DOKU Webhook: Unrecognized invoice_number format.', ['invoice_number' => $invoiceNumber]);
            return response()->json(['status' => 'ignored']);
        }

        $bookingId    = (int) $parts[1];
        $grossAmount  = (float) ($payload['order']['amount'] ?? 0);
        $paymentType  = 'doku_' . strtolower($payload['channel']['id'] ?? 'unknown');
        $transactionId = $payload['transaction']['date'] ?? $invoiceNumber;

        if ($transactionStatus === 'SUCCESS') {
            try {
                $this->processPaymentAction->execute(
                    $bookingId,
                    $paymentType,
                    $transactionId,
                    $grossAmount,
                    $payload,
                    'settlement'
                );
                Log::info("DOKU Webhook: Booking #{$bookingId} berhasil dibayar via {$paymentType}.");
            } catch (\Exception $e) {
                Log::error("DOKU Webhook: Gagal memproses Booking #{$bookingId}. Error: " . $e->getMessage());
                // Tetap return 200 agar DOKU tidak mengirim ulang
            }
        } elseif ($transactionStatus === 'FAILED') {
            Log::info("DOKU Webhook: Booking #{$bookingId} gagal/expired.", [
                'invoice_number' => $invoiceNumber,
                'status'         => $transactionStatus,
            ]);
        } else {
            Log::info("DOKU Webhook: Status tidak dikenal '{$transactionStatus}' untuk Booking #{$bookingId}.");
        }

        return response()->json(['status' => 'success']);
    }
}
