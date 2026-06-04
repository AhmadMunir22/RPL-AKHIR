<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Mengirim resi tiket via WhatsApp ke pelanggan.
     * 
     * CATATAN UNTUK TUGAS AKHIR: 
     * Fungsi ini bertindak sebagai "Mock / Dummy" API Gateway WhatsApp.
     * Dalam environment production nyata, bagian ini akan diganti dengan HTTP Request (cURL)
     * ke provider seperti Fonz, Wablas, Twilio, atau Qiscus.
     * Saat ini, sistem "mensimulasikan" pengiriman dengan mencatatnya di Log.
     */
    public function sendTicket(Booking $booking): bool
    {
        $phone = $booking->user->phone ?? null;
        if (!$phone) {
            return false;
        }

        // Format pesan WhatsApp
        $message = "Halo {$booking->user->name},\n\n";
        $message .= "Pembayaran untuk pesanan lapangan PadelBook Anda telah berhasil (LUNAS).\n";
        $message .= "Berikut adalah detail reservasi Anda:\n\n";
        $message .= "Booking ID: #{$booking->id}\n";
        $message .= "Lapangan: {$booking->court->name}\n";
        $message .= "Tanggal Main: {$booking->date->format('d F Y')}\n";
        $message .= "Kode Tiket: {$booking->qr_code}\n\n";
        $message .= "Silakan tunjukkan kode tiket ini ke resepsionis saat kedatangan.\n\n";
        $message .= "Terima kasih,\nTim PadelBook";

        // Simulasi pengiriman via API (misalnya Wablas / Twilio)
        try {
            // Http::post('https://api.wablas.com/v2/send-message', [ ... ])
            
            // Mencatat bahwa pesan "terkirim" (Simulasi)
            Log::info("WHATSAPP SENT TO {$phone}: \n" . $message);
            
            return true;
        } catch (\Exception $e) {
            Log::error("WHATSAPP FAILED TO {$phone}: " . $e->getMessage());
            return false;
        }
    }
}
