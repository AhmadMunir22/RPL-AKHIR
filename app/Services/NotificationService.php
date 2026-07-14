<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Notification;
use App\Support\PhoneHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class NotificationService
 * 
 * Bertanggung jawab mengirimkan notifikasi multi-channel (WhatsApp & Email).
 * Digunakan untuk mengirimkan e-tiket/kwitansi reservasi sukses kepada member,
 * notifikasi internal, serta kode OTP verifikasi 2FA lewat Kata AI API gateway.
 * 
 * @package App\Services
 */
class NotificationService
{
    /**
     * Mengirimkan Tanda Terima/Kwitansi Reservasi Sukses (Email & WhatsApp).
     * 
     * Mencegah duplikasi pengiriman kwitansi dengan memeriksa riwayat notifikasi.
     * 
     * @param Booking $booking Record Booking terkait
     * @return void
     */
    public function sendBookingReceipt(Booking $booking): void
    {
        // 1. Cegah duplikasi: Pastikan tiket untuk booking_id ini belum pernah dikirimkan
        $alreadySent = Notification::where('user_id', $booking->user_id)
            ->where('type', 'booking_status')
            ->get()
            ->contains(function ($notif) use ($booking) {
                return isset($notif->data['booking_id']) && (int)$notif->data['booking_id'] === (int)$booking->id;
            });

        if ($alreadySent) {
            Log::info("Booking receipt already sent for Booking #{$booking->id}, skipping.");
            return;
        }

        $slotsList = implode(', ', $booking->slots);
        $dateStr   = $booking->date->format('d M Y');
        $ticketUrl = route('dashboard.bookings.ticket', $booking->id);

        // Susun teks pesan tiket digital
        $message = "Halo {$booking->user->name},\n\n"
                 . "Reservasi Anda di PadelBook berhasil dikonfirmasi! 🎾\n\n"
                 . "=== RINCIAN TIKET ===\n"
                 . "• Lapangan: {$booking->court->name}\n"
                 . "• Tanggal: {$dateStr}\n"
                 . "• Jam Sesi: {$slotsList}\n"
                 . "• Total Harga: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n"
                 . "• Status Pembayaran: {$booking->payment_status}\n"
                 . "• Kode Tiket (QR Code): {$booking->qr_code}\n"
                 . "• Link Tiket Web: {$ticketUrl}\n\n"
                 . "Tunjukkan tiket digital Anda di resepsionis saat kedatangan untuk scan QR Code.\n\n"
                 . "Selamat berolahraga! 💪";

        // 2. Simpan record log notifikasi ke database
        Notification::create([
            'user_id' => $booking->user_id,
            'type'    => 'booking_status',
            'title'   => 'Reservasi PadelBook Berhasil!',
            'body'    => "Reservasi lapangan {$booking->court->name} pada tanggal {$dateStr} jam {$slotsList} berhasil dikonfirmasi.",
            'data'    => [
                'booking_id' => $booking->id,
                'qr_code'    => $booking->qr_code,
            ],
        ]);

        // 3. Kirim via Email
        try {
            Mail::raw($message, function ($mail) use ($booking) {
                $mail->to($booking->user->email)
                    ->subject('[PadelBook] Konfirmasi Reservasi Lapangan Padel - Tiket #' . $booking->id);
            });
            Log::info("Booking email receipt sent to {$booking->user->email}");
        } catch (\Exception $e) {
            Log::error("Email confirmation failed for Booking #{$booking->id}: " . $e->getMessage());
        }

        // 4. Kirim via WhatsApp (Dinonaktifkan sesuai permintaan)
        // if (!empty($booking->user->phone)) {
        //     $this->sendWhatsAppBookingReceipt($booking->user->phone, $booking);
        // }
    }

    /**
     * Mengirimkan Tanda Terima/Tiket Sukses via WhatsApp (Kata AI API).
     * 
     * @param string $phone Nomor WhatsApp tujuan
     * @param Booking $booking Record Booking terkait
     * @return array{ok: bool, error: ?string}
     */
    public function sendWhatsAppBookingReceipt(string $phone, Booking $booking): array
    {
        return ['ok' => true, 'error' => null]; // Dinonaktifkan total sesuai permintaan

        $token        = $this->kataAiToken();
        $target       = PhoneHelper::kataAiTarget($phone);
        $displayPhone = PhoneHelper::display($phone);

        if ($target === '' || strlen($target) < 11) {
            return ['ok' => false, 'error' => 'Nomor WhatsApp tidak valid.'];
        }

        $slotsList = implode(', ', $booking->slots);
        $dateStr   = $booking->date->format('d M Y');
        $ticketUrl = route('dashboard.bookings.ticket', $booking->id);

        // Susun teks pesan WhatsApp (dengan formatting bold markdown WA)
        $message = "🎾 *PadelBook - KONFIRMASI RESERVASI* 🎾\n\n"
                 . "Halo *{$booking->user->name}*,\n"
                 . "Reservasi Anda di PadelBook berhasil dikonfirmasi!\n\n"
                 . "*=== RINCIAN TIKET ===*\n"
                 . "• Lapangan: *{$booking->court->name}*\n"
                 . "• Tanggal: *{$dateStr}*\n"
                 . "• Jam Sesi: *{$slotsList}*\n"
                 . "• Total Harga: *Rp " . number_format($booking->total_price, 0, ',', '.') . "*\n"
                 . "• Status Pembayaran: *{$booking->payment_status}*\n"
                 . "• Kode Tiket (QR Code): *{$booking->qr_code}*\n"
                 . "• Link Tiket Web: {$ticketUrl}\n\n"
                 . "Tunjukkan pesan/tiket digital ini di resepsionis saat kedatangan untuk scan QR Code.\n\n"
                 . "Selamat berolahraga! 💪";

        // Jika token Kata AI belum dikonfigurasi di environment (.env)
        if (empty($token)) {
            Log::warning("Kata AI tidak dikonfigurasi. WhatsApp Receipt -> WA {$displayPhone}");
            if (app()->environment('local', 'testing')) {
                Log::info("DEV WA Receipt untuk {$displayPhone}: \n{$message}");
                return ['ok' => true, 'error' => null];
            }
            return ['ok' => false, 'error' => 'Layanan WhatsApp belum dikonfigurasi (KATA_AI_TOKEN).'];
        }

        // Tembak API endpoint Kata AI
        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout(30)
                ->post('https://api.kata.ai/v1/send', [
                    'target'  => $target,
                    'message' => $message,
                ]);

            $body = $response->json() ?? [];
            Log::info('Kata AI Booking Receipt WA', [
                'to'       => $displayPhone,
                'target'   => $target,
                'response' => $response->body(),
            ]);

            if ($this->kataAiResponseOk($response, $body)) {
                return ['ok' => true, 'error' => null];
            }

            $reason = $body['reason'] ?? $body['detail'] ?? $response->body();
            return ['ok' => false, 'error' => $this->humanizeKataAiError((string) $reason)];
        } catch (\Exception $e) {
            Log::error("Kata AI exception on receipt WA: {$e->getMessage()}");
            return ['ok' => false, 'error' => 'Gagal menghubungi server WhatsApp. Coba lagi.'];
        }
    }

    /**
     * Mengirimkan Kode OTP via WhatsApp (Kata AI API).
     * 
     * @param string $phone Nomor tujuan
     * @param string $otpCode Kode OTP
     * @param string $purpose Deskripsi tujuan verifikasi
     * @return array{ok: bool, error: ?string}
     */
    public function sendWhatsAppOtp(string $phone, string $otpCode, string $purpose): array
    {
        return ['ok' => true, 'error' => null]; // Dinonaktifkan total sesuai permintaan

        $token        = $this->kataAiToken();
        $target       = PhoneHelper::kataAiTarget($phone);
        $displayPhone = PhoneHelper::display($phone);

        if ($target === '' || strlen($target) < 11) {
            return ['ok' => false, 'error' => 'Nomor WhatsApp tidak valid.'];
        }

        // Jalankan simulasi lokal jika token Kata AI tidak diatur
        if (empty($token)) {
            Log::warning("Kata AI tidak dikonfigurasi. OTP {$otpCode} -> WA {$displayPhone}");
            if (app()->environment('local', 'testing')) {
                Log::info("DEV OTP untuk {$displayPhone}: {$otpCode}");
                return ['ok' => true, 'error' => null];
            }
            return ['ok' => false, 'error' => 'Layanan WhatsApp belum dikonfigurasi (KATA_AI_TOKEN).'];
        }

        // Periksa status kelayakan device WA Kata AI sebelum menembak API
        $device = $this->getKataAiDevice();
        if ($device === null) {
            return ['ok' => false, 'error' => 'Tidak dapat terhubung ke Kata AI. Periksa token API.'];
        }

        if (($device['device_status'] ?? '') !== 'connect') {
            return [
                'ok'    => false,
                'error' => 'Device WhatsApp Kata AI belum terhubung. Buka dashboard Kata AI -> scan QR WhatsApp.',
            ];
        }

        // Keamanan Kata AI: Cegah pengiriman SMS/WA ke nomor device pengirim sendiri
        $deviceNumber = (string) ($device['device'] ?? '');
        if ($deviceNumber !== '' && PhoneHelper::digitsMatch($target, $deviceNumber)) {
            return [
                'ok'    => false,
                'error' => 'Nomor pendaftaran sama dengan nomor device Kata AI. WhatsApp tidak bisa mengirim ke nomor sendiri — daftar dengan nomor WA lain.',
            ];
        }

        $message = "PadelBook - Kode OTP\n\n"
                 . "{$purpose}\n\n"
                 . "Kode: {$otpCode}\n"
                 . "Berlaku 5 menit.\n\n"
                 . "Jangan bagikan kode ini.";

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout(30)
                ->post('https://api.kata.ai/v1/send', [
                    'target'  => $target,
                    'message' => $message,
                ]);

            $body = $response->json() ?? [];
            Log::info('Kata AI OTP', [
                'to'       => $displayPhone,
                'target'   => $target,
                'response' => $response->body(),
            ]);

            if ($this->kataAiResponseOk($response, $body)) {
                return ['ok' => true, 'error' => null];
            }

            $reason = $body['reason'] ?? $body['detail'] ?? $response->body();
            return ['ok' => false, 'error' => $this->humanizeKataAiError((string) $reason)];
        } catch (\Exception $e) {
            Log::error("Kata AI exception: {$e->getMessage()}");
            return ['ok' => false, 'error' => 'Gagal menghubungi server WhatsApp. Coba lagi.'];
        }
    }

    /**
     * Membaca Kata AI Token API dari berkas konfigurasi.
     * 
     * @return string
     */
    private function kataAiToken(): string
    {
        return trim((string) config('services.kata_ai.token'), " \t\n\r\"'");
    }

    /**
     * Mengambil detail status device WhatsApp dari server Kata AI.
     * 
     * @return array<string, mixed>|null
     */
    private function getKataAiDevice(): ?array
    {
        $token = $this->kataAiToken();
        if ($token === '') {
            return null;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout(15)
                ->post('https://api.kata.ai/v1/device');

            if (!$response->successful()) {
                return null;
            }

            $body = $response->json();
            return is_array($body) ? $body : null;
        } catch (\Exception $e) {
            Log::error('Kata AI device check failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Memeriksa apakah respon dari gateway Kata AI menunjukkan status sukses.
     * 
     * @param \Illuminate\Http\Client\Response $response
     * @param array $body
     * @return bool
     */
    private function kataAiResponseOk(\Illuminate\Http\Client\Response $response, array $body): bool
    {
        if (!$response->successful()) {
            return false;
        }

        if (($body['status'] ?? false) !== true) {
            return false;
        }

        $reason = strtolower((string) ($body['reason'] ?? ''));
        if (str_contains($reason, 'invalid token') || str_contains($reason, 'disconnected')) {
            return false;
        }

        return true;
    }

    /**
     * Menghasilkan teks error yang ramah dibaca pengguna berdasarkan kode error Kata AI.
     * 
     * @param string $reason
     * @return string
     */
    private function humanizeKataAiError(string $reason): string
    {
        $lower = strtolower($reason);

        if (str_contains($lower, 'invalid token')) {
            return 'Token Kata AI tidak valid. Perbarui KATA_AI_TOKEN di file .env.';
        }
        if (str_contains($lower, 'disconnected')) {
            return 'WhatsApp Kata AI terputus. Buka dashboard Kata AI -> Device -> scan QR lagi.';
        }

        return 'WhatsApp: ' . $reason;
    }
}

