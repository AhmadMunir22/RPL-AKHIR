<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Notification;
use App\Support\PhoneHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send booking receipt details via Email and WhatsApp.
     */
    public function sendBookingReceipt(Booking $booking): void
    {
        // Prevent duplicate sending of the receipt
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
        $dateStr = $booking->date->format('d M Y');
        $ticketUrl = route('dashboard.bookings.ticket', $booking->id);

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

        Notification::create([
            'user_id' => $booking->user_id,
            'type' => 'booking_status',
            'title' => 'Reservasi PadelBook Berhasil!',
            'body' => "Reservasi lapangan {$booking->court->name} pada tanggal {$dateStr} jam {$slotsList} berhasil dikonfirmasi.",
            'data' => [
                'booking_id' => $booking->id,
                'qr_code' => $booking->qr_code,
            ],
        ]);

        // Send Email
        try {
            Mail::raw($message, function ($mail) use ($booking) {
                $mail->to($booking->user->email)
                    ->subject('[PadelBook] Konfirmasi Reservasi Lapangan Padel - Tiket #' . $booking->id);
            });
            Log::info("Booking email receipt sent to {$booking->user->email}");
        } catch (\Exception $e) {
            Log::error("Email confirmation failed for Booking #{$booking->id}: " . $e->getMessage());
        }

        // Send WhatsApp if phone number is registered
        if (!empty($booking->user->phone)) {
            $this->sendWhatsAppBookingReceipt($booking->user->phone, $booking);
        }
    }

    /**
     * Send booking receipt details via WhatsApp (Fonnte).
     *
     * @return array{ok: bool, error: ?string}
     */
    public function sendWhatsAppBookingReceipt(string $phone, Booking $booking): array
    {
        $token = $this->fonnteToken();
        $target = PhoneHelper::fonnteTarget($phone);
        $displayPhone = PhoneHelper::display($phone);

        if ($target === '' || strlen($target) < 11) {
            return ['ok' => false, 'error' => 'Nomor WhatsApp tidak valid.'];
        }

        $slotsList = implode(', ', $booking->slots);
        $dateStr = $booking->date->format('d M Y');
        $ticketUrl = route('dashboard.bookings.ticket', $booking->id);

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

        if (empty($token)) {
            Log::warning("Fonnte tidak dikonfigurasi. WhatsApp Receipt → WA {$displayPhone}");
            if (app()->environment('local', 'testing')) {
                Log::info("DEV WA Receipt untuk {$displayPhone}: \n{$message}");
                return ['ok' => true, 'error' => null];
            }
            return ['ok' => false, 'error' => 'Layanan WhatsApp belum dikonfigurasi (FONNTE_TOKEN).'];
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout(30)
                ->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                ]);

            $body = $response->json() ?? [];
            Log::info('Fonnte Booking Receipt WA', [
                'to' => $displayPhone,
                'target' => $target,
                'response' => $response->body(),
            ]);

            if ($this->fonnteResponseOk($response, $body)) {
                return ['ok' => true, 'error' => null];
            }

            $reason = $body['reason'] ?? $body['detail'] ?? $response->body();
            return ['ok' => false, 'error' => $this->humanizeFonnteError((string) $reason)];
        } catch (\Exception $e) {
            Log::error("Fonnte exception on receipt WA: {$e->getMessage()}");
            return ['ok' => false, 'error' => 'Gagal menghubungi server WhatsApp. Coba lagi.'];
        }
    }

    /**
     * Send OTP via WhatsApp (Fonnte).
     *
     * @return array{ok: bool, error: ?string}
     */
    public function sendWhatsAppOtp(string $phone, string $otpCode, string $purpose): array
    {
        $token = $this->fonnteToken();
        $target = PhoneHelper::fonnteTarget($phone);
        $displayPhone = PhoneHelper::display($phone);

        if ($target === '' || strlen($target) < 11) {
            return ['ok' => false, 'error' => 'Nomor WhatsApp tidak valid.'];
        }

        if (empty($token)) {
            Log::warning("Fonnte tidak dikonfigurasi. OTP {$otpCode} → WA {$displayPhone}");
            if (app()->environment('local', 'testing')) {
                Log::info("DEV OTP untuk {$displayPhone}: {$otpCode}");
                return ['ok' => true, 'error' => null];
            }
            return ['ok' => false, 'error' => 'Layanan WhatsApp belum dikonfigurasi (FONNTE_TOKEN).'];
        }

        $device = $this->getFonnteDevice();
        if ($device === null) {
            return ['ok' => false, 'error' => 'Tidak dapat terhubung ke Fonnte. Periksa token API.'];
        }

        if (($device['device_status'] ?? '') !== 'connect') {
            return [
                'ok' => false,
                'error' => 'Device WhatsApp Fonnte belum terhubung. Buka dashboard Fonnte → scan QR WhatsApp.',
            ];
        }

        $deviceNumber = (string) ($device['device'] ?? '');
        if ($deviceNumber !== '' && PhoneHelper::digitsMatch($target, $deviceNumber)) {
            return [
                'ok' => false,
                'error' => 'Nomor pendaftaran sama dengan nomor device Fonnte. WhatsApp tidak bisa mengirim ke nomor sendiri — daftar dengan nomor WA lain.',
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
                ->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                ]);

            $body = $response->json() ?? [];
            Log::info('Fonnte OTP', [
                'to' => $displayPhone,
                'target' => $target,
                'response' => $response->body(),
            ]);

            if ($this->fonnteResponseOk($response, $body)) {
                return ['ok' => true, 'error' => null];
            }

            $reason = $body['reason'] ?? $body['detail'] ?? $response->body();
            return ['ok' => false, 'error' => $this->humanizeFonnteError((string) $reason)];
        } catch (\Exception $e) {
            Log::error("Fonnte exception: {$e->getMessage()}");
            return ['ok' => false, 'error' => 'Gagal menghubungi server WhatsApp. Coba lagi.'];
        }
    }

    private function fonnteToken(): string
    {
        return trim((string) config('services.fonnte.token'), " \t\n\r\"'");
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getFonnteDevice(): ?array
    {
        $token = $this->fonnteToken();
        if ($token === '') {
            return null;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout(15)
                ->post('https://api.fonnte.com/device');

            if (!$response->successful()) {
                return null;
            }

            $body = $response->json();
            return is_array($body) ? $body : null;
        } catch (\Exception $e) {
            Log::error('Fonnte device check failed: ' . $e->getMessage());
            return null;
        }
    }

    private function fonnteResponseOk(\Illuminate\Http\Client\Response $response, array $body): bool
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

    private function humanizeFonnteError(string $reason): string
    {
        $lower = strtolower($reason);

        if (str_contains($lower, 'invalid token')) {
            return 'Token Fonnte tidak valid. Perbarui FONNTE_TOKEN di file .env.';
        }
        if (str_contains($lower, 'disconnected')) {
            return 'WhatsApp Fonnte terputus. Buka fonnte.com → Device → scan QR lagi.';
        }

        return 'WhatsApp: ' . $reason;
    }
}
