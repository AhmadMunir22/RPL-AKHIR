<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class PaymentService
 * 
 * Menyediakan fungsionalitas transaksi pembayaran baik melalui saldo dompet digital (Wallet)
 * maupun integrasi dengan Midtrans Payment Gateway (Snap API — QRIS, GoPay, VA Bank, dll).
 * 
 * Midtrans Snap menggunakan pendekatan token-based:
 * 1. Backend membuat Snap Token via REST API (server-to-server)
 * 2. Frontend menggunakan token tersebut untuk menampilkan popup Snap.js
 * 3. Midtrans mengirimkan Webhook Notification ke endpoint kita saat transaksi selesai
 * 
 * @package App\Services
 */
class PaymentService
{
    /**
     * Server Key Midtrans (rahasia — hanya digunakan di backend).
     */
    protected string $midtransServerKey;

    /**
     * Client Key Midtrans (aman diekspos ke frontend untuk Snap.js).
     */
    protected string $midtransClientKey;

    /**
     * Status lingkungan Midtrans (true = Production, false = Sandbox).
     */
    protected bool $midtransIsProduction;

    /**
     * URL API Snap untuk pembuatan token.
     */
    protected string $midtransSnapApiUrl;

    /**
     * PaymentService constructor.
     * 
     * Memuat konfigurasi Midtrans dari file environment (.env).
     */
    public function __construct()
    {
        $this->midtransServerKey    = config('services.midtrans.server_key', '');
        $this->midtransClientKey    = config('services.midtrans.client_key', '');
        $this->midtransIsProduction = (bool) config('services.midtrans.is_production', true);
        $this->midtransSnapApiUrl   = config('services.midtrans.snap_api_url',
            'https://app.midtrans.com/snap/v1/transactions'
        );
    }

    /**
     * Membuat Snap Token Midtrans untuk Popup Pembayaran.
     * 
     * Mengirimkan request ke Midtrans Snap API dan mendapatkan token unik
     * yang dipakai oleh Snap.js di frontend untuk memunculkan popup pembayaran.
     * 
     * Format Authentikasi: Basic Auth dengan Server Key sebagai username (password kosong).
     * 
     * @param Booking $booking Record Booking terkait
     * @param float $amountToPay Nominal pembayaran yang harus dibayar
     * @return array{snap_token: string, redirect_url: string}
     * @throws \Exception
     */
    public function getMidtransSnapToken(Booking $booking, float $amountToPay, ?string $paymentType = null): array
    {
        if (empty($this->midtransServerKey)) {
            throw new \Exception('MIDTRANS_SERVER_KEY belum dikonfigurasi di file .env.');
        }

        // 1. Susun Order ID unik agar setiap transaksi teridentifikasi
        // Format: PBDK-{booking_id}-{timestamp} — mudah di-parse saat webhook diterima
        $orderId = 'PBDK-' . $booking->id . '-' . time();

        // 2. Susun payload request sesuai struktur Midtrans Snap API
        $payload = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $amountToPay, // Midtrans minta integer (rupiah)
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email'      => $booking->user->email,
                'phone'      => $booking->user->phone ?? '',
            ],
            'item_details' => [
                [
                    'id'       => 'COURT-' . $booking->court_id,
                    'price'    => (int) $amountToPay,
                    'quantity' => 1,
                    'name'     => 'Sewa Lapangan: ' . ($booking->court->name ?? 'PadelBook'),
                ],
            ],
            'callbacks' => [
                // URL redirect setelah user selesai membayar (via Snap popup finish callback)
                'finish' => route('dashboard.bookings'),
            ],
        ];

        // 3. Skip UI Midtrans: Filter metode pembayaran agar otomatis lompat ke metode pilihan
        if ($paymentType) {
            $enabledPayments = [];
            switch ($paymentType) {
                case 'qris':
                    $enabledPayments = ['qris', 'other_qris', 'gopay']; // Fallback ke gopay (yg ada QRIS-nya) jika qris native tidak aktif
                    break;
                case 'gopay':
                    $enabledPayments = ['gopay'];
                    break;
                case 'shopeepay':
                    $enabledPayments = ['shopeepay'];
                    break;
                case 'dana':
                    $enabledPayments = ['qris', 'other_qris', 'gopay'];
                    break;
                case 'bank_transfer':
                    $enabledPayments = ['bca_va', 'bni_va', 'bri_va', 'permata_va', 'echannel', 'other_va', 'cimb_va'];
                    break;
                case 'cstore':
                    $enabledPayments = ['indomaret', 'alfamart'];
                    break;
            }
            if (!empty($enabledPayments)) {
                $payload['enabled_payments'] = $enabledPayments;
            }
        }

        Log::info('Midtrans Snap Token Request', [
            'order_id'    => $orderId,
            'booking_id'  => $booking->id,
            'amount'      => $amountToPay,
            'environment' => $this->midtransIsProduction ? 'production' : 'sandbox',
        ]);

        // 3. Kirim request ke Midtrans Snap API menggunakan Basic Auth
        // Server Key digunakan sebagai username, password dikosongkan
        try {
            $httpRequest = Http::withBasicAuth($this->midtransServerKey, '')->timeout(30);

            if (app()->environment('local')) {
                $httpRequest->withOptions(['verify' => false]);
            }

            $response = $httpRequest->post($this->midtransSnapApiUrl, $payload);

            $body = $response->json();

            Log::info('Midtrans Snap Token Response', [
                'status_code' => $response->status(),
                'body'        => $body,
            ]);

            // 4. Validasi respon sukses dari Midtrans
            if (!$response->successful() || empty($body['token'])) {
                $errMsg = $body['error_messages'][0] ?? ($body['status_message'] ?? 'Gagal mendapatkan Snap token.');
                throw new \Exception('Midtrans Error: ' . $errMsg);
            }

            return [
                'snap_token'   => $body['token'],
                'redirect_url' => $body['redirect_url'] ?? '',
                'order_id'     => $orderId,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Token Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Memverifikasi Signature Key pada Webhook Notifikasi dari Midtrans.
     * 
     * Midtrans mengirimkan signature_key = SHA512(order_id + status_code + gross_amount + server_key).
     * Kita harus memverifikasi keasliannya sebelum memproses update status.
     * 
     * @param string $orderId ID Order dari payload webhook
     * @param string $statusCode Kode status HTTP dari payload webhook
     * @param string $grossAmount Nominal transaksi dari payload webhook
     * @param string $receivedSignature Signature yang dikirim Midtrans di payload
     * @return bool
     */
    public function verifyMidtransSignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $receivedSignature
    ): bool {
        // Rumus: SHA512(order_id + status_code + gross_amount + server_key)
        $expectedSignature = hash('sha512',
            $orderId . $statusCode . $grossAmount . $this->midtransServerKey
        );

        return hash_equals($expectedSignature, $receivedSignature);
    }

    /**
     * Memproses Pembayaran Menggunakan Saldo Dompet Digital (Wallet).
     * 
     * Memotong saldo wallet user, mencatat mutasi keuangan, serta menulis Payment Ledger.
     * 
     * @param Booking $booking Record Booking terkait
     * @param float $amountToPay Nominal bayar
     * @return bool
     * @throws \Exception
     */
    public function processWalletPayment(Booking $booking, float $amountToPay): bool
    {
        return DB::transaction(function () use ($booking, $amountToPay) {
            $user = User::lockForUpdate()->find($booking->user_id);

            // Validasi kecukupan saldo wallet
            if ($user->wallet_balance < $amountToPay) {
                throw new \Exception('Saldo dompet digital Anda tidak mencukupi.');
            }

            // 1. Kurangi saldo digital wallet pengguna
            $user->decrement('wallet_balance', $amountToPay);

            // 2. Simpan catatan mutasi pengeluaran wallet
            WalletTransaction::create([
                'user_id'     => $user->id,
                'type'        => 'payment',
                'amount'      => $amountToPay,
                'description' => 'Pembayaran Booking PadelBook #' . $booking->id,
                'ref_id'      => $booking->id,
            ]);

            // 3. Simpan Payment Ledger
            Payment::create([
                'booking_id'     => $booking->id,
                'gateway'        => 'wallet',
                'transaction_id' => 'WAL-' . $booking->id . '-' . time(),
                'amount'         => $amountToPay,
                'status'         => 'settlement',
                'payload'        => ['wallet_balance_after' => $user->wallet_balance]
            ]);

            return true;
        });
    }

    /**
     * Memproses Top-up Saldo Wallet Digital.
     * 
     * @param int $userId ID Pengguna
     * @param float $amount Jumlah uang top-up
     * @param string $refId ID Referensi Top-up
     * @return void
     */
    public function topUpWallet(int $userId, float $amount, string $refId): void
    {
        DB::transaction(function () use ($userId, $amount, $refId) {
            $user = User::lockForUpdate()->find($userId);
            
            // Increment saldo wallet user
            $user->increment('wallet_balance', $amount);

            // Simpan log mutasi penambahan saldo top-up
            WalletTransaction::create([
                'user_id'     => $userId,
                'type'        => 'deposit',
                'amount'      => $amount,
                'description' => 'Top Up Saldo Dompet Digital PadelBook',
                'ref_id'      => $refId,
            ]);
        });
    }

    /**
     * Memproses Pengembalian Saldo (Refund) ke Dompet Digital.
     * 
     * @param Booking $booking Record Booking terkait
     * @param float $amount Nominal refund
     * @param string $reason Alasan spesifik refund (default 'Pembatalan Booking')
     * @return void
     */
    public function refundToWallet(Booking $booking, float $amount, string $reason = 'Pembatalan Booking'): void
    {
        DB::transaction(function () use ($booking, $amount, $reason) {
            $user = User::lockForUpdate()->find($booking->user_id);
            
            // Tambahkan nominal uang ke saldo wallet user
            $user->increment('wallet_balance', $amount);

            // Catat transaksi mutasi penambahan saldo refund
            WalletTransaction::create([
                'user_id'     => $user->id,
                'type'        => 'refund',
                'amount'      => $amount,
                'description' => 'Refund: ' . $reason . ' (Booking #' . $booking->id . ')',
                'ref_id'      => $booking->id,
            ]);
        });
    }
}
