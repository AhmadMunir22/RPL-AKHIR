<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\LoyaltyPoint;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

/**
 * Class ProcessPaymentAction
 * 
 * Melayani penyelesaian transaksi pembayaran pasca respon sukses dari gateway.
 * Mengubah status pemesanan, mencatat riwayat transaksi keuangan pada tabel `payments`,
 * serta mengirimkan notifikasi internal kepada admin/operator lapangan.
 * 
 * @package App\Actions
 */
class ProcessPaymentAction
{
    /**
     * Service untuk pengiriman WhatsApp/Email notifikasi.
     */
    protected NotificationService $notificationService;

    /**
     * ProcessPaymentAction constructor.
     * 
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Eksekusi Penyelesaian Pembayaran & Konfirmasi Status Booking.
     * 
     * @param int $bookingId ID dari Booking
     * @param string $gateway Nama Gateway Pembayaran (misal wallet, midtrans_gopay, dll)
     * @param string $transactionId ID Transaksi Unik dari Gateway
     * @param float $amount Nominal uang yang dibayarkan
     * @param array $payload Respon mentah/log JSON dari Gateway
     * @param string $status Status transaksi gateway (default 'settlement')
     * @return Booking
     */
    public function execute(
        int $bookingId,
        string $gateway,
        string $transactionId,
        float $amount,
        array $payload,
        string $status = 'settlement'
    ): Booking {
        // Proses update data secara aman menggunakan lockForUpdate dalam DB Transaction
        $booking = DB::transaction(function () use ($bookingId, $gateway, $transactionId, $amount, $payload, $status) {
            $booking = Booking::lockForUpdate()->findOrFail($bookingId);

            // Jika status booking sudah lunas terkonfirmasi, lewati proses untuk menghindari duplikasi
            if ($booking->payment_status === 'paid' && $booking->status === 'confirmed') {
                return $booking;
            }

            // 1. Tentukan status pembayaran akhir: "paid" (lunas) atau "partial" (DP)
            $paymentStatus = 'paid';

            // Jika dana yang dibayar kurang dari harga total tetapi mencukupi batas minimum DP 50%
            if ($amount < $booking->total_price && $amount >= $booking->dp_amount) {
                $paymentStatus = 'partial';
            }

            $booking->update([
                'payment_status' => $paymentStatus,
                'payment_method' => $gateway,
                'status'         => 'confirmed', // Otomatis confirmed setelah lunas/DP dibayar
            ]);

            // 2. Catat riwayat pembayaran (Payment Ledger)
            Payment::updateOrCreate(
                ['booking_id' => $booking->id, 'transaction_id' => $transactionId],
                [
                    'gateway'        => $gateway,
                    'amount'         => $amount,
                    'status'         => $status,
                    'payload'        => $payload
                ]
            );

            // 3. Kirim notifikasi internal untuk Admin & Operator Lapangan
            $booking->loadMissing('court');
            $slotsStr = is_array($booking->slots) ? implode(', ', $booking->slots) : '';
            $dateStr  = $booking->date ? $booking->date->format('d M Y') : '';

            $admins = User::whereIn('role', ['super_admin', 'operator'])->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type'    => 'booking_status',
                    'title'   => 'Pembayaran Berhasil',
                    'body'    => "Pembayaran berhasil. Lap: {$booking->court->name}, Tanggal: {$dateStr}, Jam: {$slotsStr}.",
                    'data'    => [
                        'booking_id' => $booking->id,
                    ],
                ]);
            }

            // 4. Kirim E-Tiket Digital (WhatsApp & Email) ke Pelanggan secara otomatis
            $this->notificationService->sendBookingReceipt($booking);

            return $booking;
        });

        return $booking;
    }
}

