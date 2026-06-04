<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\BookingService;

class BookingObserver
{
    public function __construct(protected BookingService $bookingService)
    {
    }

    public function updated(Booking $booking): void
    {
        if (!$booking->wasChanged(['status', 'payment_status'])) {
            return;
        }

        if ($this->bookingService->bookingHoldsSlots($booking)) {
            $this->bookingService->syncSlotsForBooking($booking);
        } else {
            $this->bookingService->releaseSlotsForBooking($booking);
        }
    }

    public function deleted(Booking $booking): void
    {
        $this->bookingService->releaseSlotsForBooking($booking);
    }
}
