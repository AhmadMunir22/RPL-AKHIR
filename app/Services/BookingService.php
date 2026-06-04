<?php

namespace App\Services;

use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Voucher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /** Status booking yang masih menahan slot. */
    public const HOLDING_STATUSES = ['pending', 'confirmed', 'completed'];

    /** Pembayaran gagal/refund → slot dilepas. */
    public const RELEASED_PAYMENT_STATUSES = ['failed', 'refunded'];

    /**
     * Jam operasional lapangan.
     */
    public function getOperationalSlots(): array
    {
        return [
            '10:00', '11:00', '12:00', '13:00', '14:00', '15:00',
            '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00',
        ];
    }

    /**
     * Apakah booking masih menahan slot di kalender?
     */
    public function bookingHoldsSlots(Booking $booking): bool
    {
        if (!in_array($booking->status, self::HOLDING_STATUSES, true)) {
            return false;
        }

        if (in_array($booking->payment_status, self::RELEASED_PAYMENT_STATUSES, true)) {
            return false;
        }

        return true;
    }

    /**
     * Daftar jam yang sudah dipesan / diblokir (tanpa cache — untuk transaksi).
     *
     * @return array<int, string>
     */
    public function getBookedSlots(int $courtId, string $date, bool $useCache = true): array
    {
        $resolver = function () use ($courtId, $date) {
            return $this->resolveBookedSlots($courtId, $date);
        };

        if (!$useCache) {
            return $resolver();
        }

        $cacheKey = "court_{$courtId}_avail_{$date}";

        return Cache::remember($cacheKey, 15, $resolver);
    }

    /**
     * @return array<int, string>
     */
    protected function resolveBookedSlots(int $courtId, string $date): array
    {
        $booked = [];

        if ($this->bookingSlotsTableExists()) {
            $booked = array_merge($booked, $this->getBookedSlotsFromTable($courtId, $date));
        }

        $booked = array_merge($booked, $this->getBookedSlotsFromBookingsJson($courtId, $date));

        $blocked = BlockedSlot::where('court_id', $courtId)
            ->whereDate('date', $date)
            ->get()
            ->pluck('slots')
            ->flatten()
            ->all();

        return array_values(array_unique(array_merge($booked, $blocked)));
    }

    protected function bookingSlotsTableExists(): bool
    {
        return DB::getSchemaBuilder()->hasTable('booking_slots');
    }

    /**
     * @return array<int, string>
     */
    protected function getBookedSlotsFromTable(int $courtId, string $date): array
    {
        return BookingSlot::query()
            ->where('booking_slots.court_id', $courtId)
            ->whereDate('booking_slots.date', $date)
            ->join('bookings', 'bookings.id', '=', 'booking_slots.booking_id')
            ->whereNull('bookings.deleted_at')
            ->whereIn('bookings.status', self::HOLDING_STATUSES)
            ->whereNotIn('bookings.payment_status', self::RELEASED_PAYMENT_STATUSES)
            ->pluck('booking_slots.slot')
            ->all();
    }

    /**
     * Fallback: booking lama yang belum punya baris di booking_slots.
     *
     * @return array<int, string>
     */
    protected function getBookedSlotsFromBookingsJson(int $courtId, string $date): array
    {
        $slots = [];

        Booking::query()
            ->where('court_id', $courtId)
            ->whereDate('date', $date)
            ->whereIn('status', self::HOLDING_STATUSES)
            ->whereNotIn('payment_status', self::RELEASED_PAYMENT_STATUSES)
            ->each(function (Booking $booking) use (&$slots) {
                if (is_array($booking->slots)) {
                    foreach ($booking->slots as $slot) {
                        $slots[] = $slot;
                    }
                }
            });

        return $slots;
    }

    /**
     * Ketersediaan per jam untuk kalender / API.
     */
    public function getAvailability(int $courtId, string $date): array
    {
        Court::findOrFail($courtId);
        $booked = $this->getBookedSlots($courtId, $date, true);

        $availability = [];
        foreach ($this->getOperationalSlots() as $slot) {
            $availability[] = [
                'slot' => $slot,
                'is_available' => !in_array($slot, $booked, true),
            ];
        }

        return $availability;
    }

    /**
     * Validasi ketat sebelum checkout / reserve (tanpa cache).
     *
     * @param  array<int, string>  $slots
     */
    public function assertSlotsAvailable(int $courtId, string $date, array $slots): void
    {
        $slots = array_values(array_unique($slots));
        $operational = $this->getOperationalSlots();

        foreach ($slots as $slot) {
            if (!in_array($slot, $operational, true)) {
                throw new \RuntimeException("Jam {$slot} tidak tersedia untuk booking.");
            }
        }

        $booked = $this->getBookedSlots($courtId, $date, false);

        foreach ($slots as $slot) {
            if (in_array($slot, $booked, true)) {
                throw new \RuntimeException("Jam {$slot} sudah dipesan. Silakan pilih jam lain.");
            }
        }
    }

    /**
     * Simpan slot ke booking_slots (unique DB mencegah double booking).
     *
     * @param  array<int, string>  $slots
     */
    public function reserveSlotsForBooking(Booking $booking, array $slots): void
    {
        if (!$this->bookingSlotsTableExists()) {
            return;
        }

        $date = $booking->date->format('Y-m-d');

        foreach ($slots as $slot) {
            BookingSlot::create([
                'booking_id' => $booking->id,
                'court_id' => $booking->court_id,
                'date' => $date,
                'slot' => $slot,
            ]);
        }

        $this->clearAvailabilityCache($booking->court_id, $date);
    }

    /**
     * Lepas slot saat booking dibatalkan / gagal bayar.
     */
    public function releaseSlotsForBooking(Booking $booking): void
    {
        if (!$this->bookingSlotsTableExists()) {
            $this->clearAvailabilityCache($booking->court_id, $booking->date->format('Y-m-d'));
            return;
        }

        BookingSlot::where('booking_id', $booking->id)->delete();
        $this->clearAvailabilityCache($booking->court_id, $booking->date->format('Y-m-d'));
    }

    /**
     * Sinkronkan booking_slots dari data booking (setelah migrate / data lama).
     */
    public function syncSlotsForBooking(Booking $booking): void
    {
        if (!$this->bookingSlotsTableExists() || !$this->bookingHoldsSlots($booking)) {
            $this->releaseSlotsForBooking($booking);
            return;
        }

        $date = $booking->date->format('Y-m-d');
        $slots = is_array($booking->slots) ? $booking->slots : [];

        BookingSlot::where('booking_id', $booking->id)->delete();

        foreach ($slots as $slot) {
            try {
                BookingSlot::create([
                    'booking_id' => $booking->id,
                    'court_id' => $booking->court_id,
                    'date' => $date,
                    'slot' => $slot,
                ]);
            } catch (\Illuminate\Database\QueryException) {
                // Slot sudah dipakai booking lain — abaikan baris duplikat untuk sync
            }
        }

        $this->clearAvailabilityCache($booking->court_id, $date);
    }

    public function clearAvailabilityCache(int $courtId, string $date): void
    {
        Cache::forget("court_{$courtId}_avail_{$date}");
    }

    /**
     * Calculate booking prices, discounts, and payment totals.
     */
    public function calculatePricing(int $courtId, array $slots, ?string $voucherCode = null): array
    {
        $court = Court::findOrFail($courtId);
        $hourlyPrice = $court->price_per_hour;
        $slotCount = count($slots);
        $subtotal = $hourlyPrice * $slotCount;

        $discount = 0.00;
        $voucherId = null;

        if ($voucherCode) {
            $voucher = Voucher::where('code', $voucherCode)->first();
            if ($voucher && $voucher->isValidFor($subtotal)) {
                $discount = $voucher->calculateDiscount($subtotal, $hourlyPrice);
                $voucherId = $voucher->id;
            }
        }

        $totalPrice = max(0.00, $subtotal - $discount);
        $dpAmount = round($totalPrice * 0.50, 2);

        return [
            'court_id' => $courtId,
            'hourly_price' => $hourlyPrice,
            'slot_count' => $slotCount,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'voucher_id' => $voucherId,
            'total_price' => $totalPrice,
            'dp_amount' => $dpAmount,
        ];
    }
}
