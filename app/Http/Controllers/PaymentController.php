<?php

namespace App\Http\Controllers;

use App\Actions\ProcessPaymentAction;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Class PaymentController
 * 
 * Mengelola pemrosesan transaksi pembayaran lapangan padel.
 * Mendukung opsi pembayaran:
 * - Saldo dompet digital (Wallet)
 * - Midtrans Snap (QRIS, GoPay, DANA, ShopeePay, VA Bank, Minimarket, dll)
 * - Upload resi transfer bank manual
 * 
 * Alur Midtrans Snap:
 * 1. Frontend request Snap Token → `payWithMidtrans()`
 * 2. Backend buat token via Midtrans API → kembalikan snap_token ke frontend
 * 3. Frontend tampilkan popup Snap.js menggunakan token tersebut
 * 4. Midtrans kirim webhook ke `midtransNotification()` saat transaksi selesai
 * 5. Backend verifikasi signature SHA512 → update status booking
 * 
 * @package App\Http\Controllers
 */
class PaymentController extends Controller
{
    /**
     * Service pengelola logika transaksi Midtrans & Wallet.
     */
    protected PaymentService $paymentService;

    /**
     * Action untuk pembaruan status pembayaran & pemicu notifikasi admin.
     */
    protected ProcessPaymentAction $processPaymentAction;

    /**
     * PaymentController constructor.
     * 
     * @param PaymentService $paymentService
     * @param ProcessPaymentAction $processPaymentAction
     */
    public function __construct(PaymentService $paymentService, ProcessPaymentAction $processPaymentAction)
    {
        $this->paymentService       = $paymentService;
        $this->processPaymentAction = $processPaymentAction;
    }

    /**
     * Memproses Pembayaran Menggunakan Saldo Wallet Pengguna.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function payWithWallet(Request $request): JsonResponse
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
            // 1. Debet saldo wallet digital dan simpan log mutasi transaksi
            $this->paymentService->processWalletPayment($booking, $request->pay_amount);
            
            // 2. Eksekusi konfirmasi lunas pada record booking secara transaksional
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
     * Membuat Snap Token Midtrans untuk Popup Pembayaran.
     * 
     * Endpoint ini dipanggil oleh frontend (AJAX) untuk mendapatkan snap_token.
     * Token kemudian digunakan Snap.js untuk memunculkan popup pembayaran Midtrans.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function payWithMidtrans(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id'   => 'required|exists:bookings,id',
            'pay_amount'   => 'required|numeric|min:0',
            'payment_type' => 'nullable|string',
        ]);

        $booking = Booking::with(['user', 'court'])->findOrFail($request->booking_id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            // Minta Snap Token dari Midtrans API (server-to-server)
            $result = $this->paymentService->getMidtransSnapToken($booking, $request->pay_amount, $request->payment_type);

            return response()->json([
                'success'      => true,
                'snap_token'   => $result['snap_token'],
                'redirect_url' => $result['redirect_url'],
                'order_id'     => $result['order_id'],
                // Client key dibutuhkan Snap.js untuk inisialisasi
                'client_key'   => config('services.midtrans.client_key'),
            ]);
        } catch (\Exception $e) {
            Log::error('Midtrans payWithMidtrans error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Menangani Webhook Notification dari Server Midtrans.
     * 
     * Midtrans mengirimkan POST request ke endpoint ini setiap kali terjadi
     * perubahan status transaksi (settlement, pending, cancel, expire, dll).
     * 
     * Keamanan: Setiap notifikasi diverifikasi menggunakan signature key
     * SHA512(order_id + status_code + gross_amount + server_key).
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function midtransNotification(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Midtrans Notification Received', $payload);

        // 1. Ekstrak field-field penting dari payload webhook Midtrans
        $orderId           = $payload['order_id']           ?? null;
        $statusCode        = $payload['status_code']        ?? null;
        $grossAmount       = $payload['gross_amount']        ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus       = $payload['fraud_status']       ?? null;
        $paymentType       = $payload['payment_type']       ?? 'midtrans';
        $transactionId     = $payload['transaction_id']     ?? $orderId;
        $signatureKey      = $payload['signature_key']      ?? null;

        // 2. Pastikan semua field wajib hadir
        if (!$orderId || !$statusCode || !$grossAmount || !$transactionStatus) {
            Log::warning('Midtrans Notification: Field tidak lengkap.', $payload);
            return response()->json(['status' => 'bad_request'], 400);
        }

        // 3. Verifikasi Signature Key — pastikan webhook benar-benar dari Midtrans
        if ($signatureKey) {
            $isValid = $this->paymentService->verifyMidtransSignature(
                $orderId, $statusCode, $grossAmount, $signatureKey
            );
            if (!$isValid) {
                Log::warning('Midtrans Notification: Signature tidak valid.', [
                    'order_id'      => $orderId,
                    'received_sig'  => $signatureKey,
                ]);
                return response()->json(['status' => 'unauthorized'], 401);
            }
        } else {
            // Jika tidak ada signature (request tidak aman), log saja sebagai warning
            Log::warning('Midtrans Notification: Tidak ada signature_key dalam payload.');
        }

        // 4. Parse Booking ID dari Order ID
        // Format Order ID: "PBDK-{booking_id}-{timestamp}" → parts[1] = booking_id
        $parts = explode('-', $orderId);
        if (count($parts) < 2 || strtoupper($parts[0]) !== 'PBDK') {
            Log::warning('Midtrans Notification: Format order_id tidak dikenal.', ['order_id' => $orderId]);
            return response()->json(['status' => 'ignored']);
        }

        $bookingId   = (int) $parts[1];
        $amountFloat = (float) str_replace(',', '', $grossAmount); // Midtrans format: "150000.00"

        // 5. Proses berdasarkan status transaksi Midtrans
        // Referensi: https://docs.midtrans.com/docs/getting-notified-by-midtrans
        if ($transactionStatus === 'capture') {
            // "capture" digunakan untuk pembayaran kartu kredit
            // Hanya konfirmasi jika fraud_status = 'accept'
            if ($fraudStatus === 'accept') {
                $this->confirmPayment($bookingId, 'midtrans_' . $paymentType, $transactionId, $amountFloat, $payload);
            }
        } elseif ($transactionStatus === 'settlement') {
            // "settlement" = dana sudah diterima sepenuhnya (GoPay, VA, QRIS, dll)
            $this->confirmPayment($bookingId, 'midtrans_' . $paymentType, $transactionId, $amountFloat, $payload);
        } elseif (in_array($transactionStatus, ['cancel', 'expire', 'deny'], true)) {
            // Transaksi dibatalkan/kadaluarsa/ditolak — log saja, tidak update booking ke failed otomatis
            Log::info("Midtrans Notification: Transaksi {$transactionStatus} untuk Booking #{$bookingId}.", [
                'order_id'           => $orderId,
                'transaction_status' => $transactionStatus,
            ]);
        } elseif ($transactionStatus === 'pending') {
            // Transaksi masih menunggu (misal: VA belum dibayar) — tidak perlu aksi
            Log::info("Midtrans Notification: Transaksi pending untuk Booking #{$bookingId}.");
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Helper: Mengkonfirmasi Pembayaran Berhasil dan Update Status Booking.
     * 
     * @param int $bookingId
     * @param string $gateway
     * @param string $transactionId
     * @param float $amount
     * @param array $payload
     * @return void
     */
    private function confirmPayment(
        int $bookingId,
        string $gateway,
        string $transactionId,
        float $amount,
        array $payload
    ): void {
        try {
            $this->processPaymentAction->execute(
                $bookingId,
                $gateway,
                $transactionId,
                $amount,
                $payload,
                'settlement'
            );
            Log::info("Midtrans: Booking #{$bookingId} berhasil dibayar via {$gateway}.");
        } catch (\Exception $e) {
            Log::error("Midtrans: Gagal memproses Booking #{$bookingId}. Error: " . $e->getMessage());
        }
    }

    /**
     * Mengunggah Bukti Pembayaran Transfer Bank Manual.
     * 
     * @param Request $request
     * @param int $id ID dari Booking
     * @return RedirectResponse
     */
    public function uploadReceipt(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'receipt' => 'required|image|mimes:jpeg,png,jpg|max:2048', // batas berkas resi maksimal 2MB
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        // Simpan berkas gambar resi ke folder storage public/receipts
        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('receipts', 'public');
            $booking->update([
                'payment_receipt' => $path,
                'payment_status'  => 'awaiting_approval', // status menunggu validasi manual admin
                'payment_method'  => 'bank_transfer',
            ]);
        }

        return redirect()->route('dashboard.bookings')->with('success', 'Bukti transfer berhasil diunggah. Menunggu persetujuan Admin.');
    }

    /**
     * Simulasi Pembayaran Berhasil (Mock Payment) untuk Keperluan Pengujian Lokal.
     * 
     * PERHATIAN: Nonaktifkan endpoint ini di lingkungan produksi penuh.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function payMock(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'pay_amount' => 'required|numeric|min:0',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        // Langsung konfirmasi sukses lunas tanpa memanggil gateway asli
        $this->processPaymentAction->execute(
            $booking->id,
            'midtrans_mock',
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
}
