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
                // Status sesi tidak diubah otomatis (tetap pending) agar admin bisa menyetujuinya secara manual
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

            // Ensure court is loaded
            $booking->loadMissing('court');
            $slotsStr = is_array($booking->slots) ? implode(', ', $booking->slots) : '';
            $dateStr = $booking->date ? $booking->date->format('d M Y') : '';

            // Notify Admins
            $admins = User::whereIn('role', ['super_admin', 'operator'])->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'booking_status',
                    'title' => 'Pembayaran Berhasil',
                    'body' => "Pembayaran berhasil. Lap: {$booking->court->name}, Tanggal: {$dateStr}, Jam: {$slotsStr}.",
                    'data' => [
                        'booking_id' => $booking->id,
                    ],
                ]);
            }

            // Tiket WhatsApp/Email tidak dikirim otomatis di sini.
            // Akan dikirim ketika admin mengubah status reservasi menjadi Confirmed / Completed.

            return $booking;
        });

        return $booking;
    }
}
