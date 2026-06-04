<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected string $dokuClientId;
    protected string $dokuSecretKey;
    protected bool $dokuIsProduction;

    public function __construct()
    {
        $this->dokuClientId = env('DOKU_CLIENT_ID', '');
        $this->dokuSecretKey = env('DOKU_SECRET_KEY', '');
        $this->dokuIsProduction = env('DOKU_IS_PRODUCTION', false);
    }



    /**
     * Get DOKU Checkout URL for payment.
     */
    public function getDokuCheckoutUrl(Booking $booking, float $amountToPay, ?string $paymentType = null): string
    {
        $targetPath = '/checkout/v1/payment';
        $baseUrl = $this->dokuIsProduction 
            ? 'https://api.doku.com' 
            : 'https://api-sandbox.doku.com';

        $requestId = (string) \Illuminate\Support\Str::uuid();
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");

        $body = [
            "order" => [
                "amount" => (int) $amountToPay,
                "invoice_number" => "BOOK-" . $booking->id . "-" . time(),
                "callback_url" => route('dashboard.bookings'),
                "auto_redirect" => true
            ],
            "payment" => [
                "payment_due_date" => 60
            ],
            "customer" => [
                "id" => (string) $booking->user_id,
                "name" => $booking->user->name,
                "email" => $booking->user->email,
                "phone" => $booking->user->phone ?? "081234567890"
            ]
        ];

        // We omit 'payment_method_types' so DOKU automatically displays 
        // all active payment channels configured in the merchant dashboard.

        $jsonBody = json_encode($body);
        $digest = base64_encode(hash('sha256', $jsonBody, true));

        $signatureComponent = "Client-Id:" . $this->dokuClientId . "\n"
            . "Request-Id:" . $requestId . "\n"
            . "Request-Timestamp:" . $timestamp . "\n"
            . "Request-Target:" . $targetPath . "\n"
            . "Digest:" . $digest;

        $signature = base64_encode(hash_hmac('sha256', $signatureComponent, $this->dokuSecretKey, true));

        try {
            $response = Http::withHeaders([
                'Client-Id' => $this->dokuClientId,
                'Request-Id' => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature' => "HMACSHA256=" . $signature,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . $targetPath, $body);

            if ($response->successful()) {
                $data = $response->json();
                return $data['response']['payment']['url'] ?? throw new \Exception('URL pembayaran tidak ditemukan di respon DOKU.');
            }

            Log::error('DOKU API error: ' . $response->body());
            $errorData = $response->json();
            $detailedMsg = $errorData['error']['message'] ?? $response->body();
            throw new \Exception('Gagal mendapatkan URL pembayaran dari DOKU: ' . $detailedMsg);
        } catch (\Exception $e) {
            Log::error('DOKU Connection Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Pay with Wallet.
     */
    public function processWalletPayment(Booking $booking, float $amountToPay): bool
    {
        return DB::transaction(function () use ($booking, $amountToPay) {
            $user = User::lockForUpdate()->find($booking->user_id);

            if ($user->wallet_balance < $amountToPay) {
                throw new \Exception('Saldo dompet digital Anda tidak mencukupi.');
            }

            // Deduct balance
            $user->decrement('wallet_balance', $amountToPay);

            // Record transaction log
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'payment',
                'amount' => $amountToPay,
                'description' => 'Pembayaran Booking PadelBook #' . $booking->id,
                'ref_id' => $booking->id,
            ]);

            // Save Payment ledger
            Payment::create([
                'booking_id' => $booking->id,
                'gateway' => 'wallet',
                'transaction_id' => 'WAL-' . $booking->id . '-' . time(),
                'amount' => $amountToPay,
                'status' => 'settlement',
                'payload' => ['wallet_balance_after' => $user->wallet_balance]
            ]);

            return true;
        });
    }

    /**
     * Top-up Wallet balance.
     */
    public function topUpWallet(int $userId, float $amount, string $refId): void
    {
        DB::transaction(function () use ($userId, $amount, $refId) {
            $user = User::lockForUpdate()->find($userId);
            $user->increment('wallet_balance', $amount);

            WalletTransaction::create([
                'user_id' => $userId,
                'type' => 'deposit',
                'amount' => $amount,
                'description' => 'Top Up Saldo Dompet Digital PadelBook',
                'ref_id' => $refId,
            ]);
        });
    }

    /**
     * Process Refund to Wallet.
     */
    public function refundToWallet(Booking $booking, float $amount, string $reason = 'Pembatalan Booking'): void
    {
        DB::transaction(function () use ($booking, $amount, $reason) {
            $user = User::lockForUpdate()->find($booking->user_id);
            $user->increment('wallet_balance', $amount);

            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'refund',
                'amount' => $amount,
                'description' => 'Refund: ' . $reason . ' (Booking #' . $booking->id . ')',
                'ref_id' => $booking->id,
            ]);
        });
    }
}
