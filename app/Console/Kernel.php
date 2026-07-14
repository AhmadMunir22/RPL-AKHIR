<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 1. Auto-cancel pending bookings older than 2 hours without payment + lepas slot
        $schedule->call(function () {
            $service = app(\App\Services\BookingService::class);

            \App\Models\Booking::where('payment_status', 'pending')
                ->where('created_at', '<=', now()->subHours(2))
                ->whereIn('status', ['pending'])
                ->each(function (\App\Models\Booking $booking) use ($service) {
                    $booking->update([
                        'status' => 'cancelled',
                        'payment_status' => 'failed',
                    ]);
                    $service->releaseSlotsForBooking($booking);
                });
        })->hourly();

        // 2. Reminder booking H-1 via Email (runs daily at 08:00)
        $schedule->call(function () {
            $tomorrow = now()->addDay()->toDateString();
            $bookings = \App\Models\Booking::with(['user', 'court'])
                ->where('status', 'confirmed')
                ->whereDate('date', $tomorrow)
                ->get();

            $notifier = new \App\Services\NotificationService();
            foreach ($bookings as $booking) {
                try {
                    $slots   = implode(', ', $booking->slots);
                    $subject = '[PadelBook] Reminder Sesi Besok - ' . $booking->court->name;
                    $message = "Halo {$booking->user->name},\n\n"
                             . "Mengingatkan sesi main padel Anda di PadelBook besok! 🎾\n\n"
                             . "• Lapangan: {$booking->court->name}\n"
                             . "• Tanggal: " . $booking->date->format('d M Y') . "\n"
                             . "• Jam Sesi: {$slots}\n\n"
                             . "Jangan terlambat dan tunjukkan tiket digital Anda saat kedatangan.\n\n"
                             . "Selamat berolahraga! 💪\nTim PadelBook";

                    \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($booking, $subject) {
                        $mail->to($booking->user->email)->subject($subject);
                    });
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Reminder email failed for Booking #{$booking->id}: " . $e->getMessage());
                }
            }
        })->dailyAt('08:00');

        // 3. Auto-update status to "completed" once the playing slots expire
        $schedule->call(function () {
            // Find bookings that have finished today
            $today = now()->toDateString();
            $currentHour = now()->format('H:i');

            $bookings = \App\Models\Booking::where('status', 'confirmed')
                ->whereDate('date', '<=', $today)
                ->get();

            foreach ($bookings as $booking) {
                // Get the last slot (e.g. slots = ["18:00", "19:00"] -> last slot is "19:00")
                $lastSlot = collect($booking->slots)->last();
                
                // Add 1 hour to estimate finish time (e.g., last slot is 19:00, it ends at 20:00)
                $endTime = \Carbon\Carbon::createFromFormat('H:i', $lastSlot)->addHour()->format('H:i');

                if ($booking->date->toDateString() < $today || ($booking->date->toDateString() === $today && $currentHour >= $endTime)) {
                    $booking->update(['status' => 'completed']);
                }
            }
        })->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
