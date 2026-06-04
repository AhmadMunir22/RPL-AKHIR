<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncBookingSlots extends Command
{
    protected $signature = 'bookings:sync-slots';

    protected $description = 'Sinkronkan jam booking ke tabel booking_slots (cegah double booking)';

    public function handle(BookingService $bookingService): int
    {
        if (!DB::getSchemaBuilder()->hasTable('booking_slots')) {
            $this->error('Tabel booking_slots belum ada. Jalankan: php artisan migrate');

            return self::FAILURE;
        }

        $bookings = Booking::query()
            ->whereIn('status', BookingService::HOLDING_STATUSES)
            ->whereNotIn('payment_status', BookingService::RELEASED_PAYMENT_STATUSES)
            ->get();

        $synced = 0;

        foreach ($bookings as $booking) {
            $bookingService->syncSlotsForBooking($booking);
            $synced++;
            $this->line("  ✓ Booking #{$booking->id} — " . implode(', ', $booking->slots ?? []));
        }

        $this->info("Selesai. {$synced} booking disinkronkan.");

        return self::SUCCESS;
    }
}
