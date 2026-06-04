<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\LoyaltyPoint;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class ProcessPaymentAction
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Complete payment and confirm the booking transaction.
     */
    public function execute(int $bookingId, string $gateway, string $transactionId, float $amount, array $payload, string $status = 'settlement'): Booking
    {
        $booking = DB::transaction(function () use ($bookingId, $gateway, $transactionId, $amount, $payload, $status) {
            $booking = Booking::lockForUpdate()->findOrFail($bookingId);

            // If already processed, skip duplication
            if ($booking->payment_status === 'paid' && $booking->status === 'confirmed') {
                return $booking;
            }

            // Update booking details
            $paymentStatus = 'paid';

            // Check if user paid full amount or partial DP
            if ($amount < $booking->total_price && $amount >= $booking->dp_amount) {
                $paymentStatus = 'partial';
            }

            $booking->update([
                'payment_status' => $paymentStatus,
                'payment_method' => $gateway,
                'status' => 'confirmed',
            ]);

            // Save Payment ledger
            Payment::updateOrCreate(
                ['booking_id' => $booking->id, 'transaction_id' => $transactionId],
                [
                    'gateway' => $gateway,
                    'amount' => $amount,
                    'status' => $status,
                    'payload' => $payload
                ]
            );

            // Grant loyalty points: 1 point per booking
            $pointsEarned = 1;
            if ($pointsEarned > 0) {
                $user = User::lockForUpdate()->find($booking->user_id);
                $user->increment('points', $pointsEarned);

                \App\Models\LoyaltyPoint::create([
                    'user_id' => $user->id,
                    'points' => $pointsEarned,
                    'type' => 'earn',
                    'description' => 'Booking Lapangan PadelBook #' . $booking->id
                ]);
            }

            // Trigger Otomatisasi Pengiriman Resi via WhatsApp (Simulasi / API Gateway)
            app(\App\Services\WhatsAppService::class)->sendTicket($booking);

            return $booking;
        });

        // Send booking receipt (Email + WhatsApp) AFTER the transaction commits
        try {
            $booking->load(['user', 'court']);
            $this->notificationService->sendBookingReceipt($booking);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send booking receipt for Booking #{$booking->id}: " . $e->getMessage());
        }

        return $booking;
    }
}
