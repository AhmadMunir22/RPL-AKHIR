<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Voucher;
use App\Services\BookingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateBookingAction
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Buat booking dengan proteksi double booking 100%.
     *
     * Strategi untuk SQLite (tidak support row-level lock):
     * 1. Seluruh proses dalam DB::transaction (SQLite serializes writes)
     * 2. INSERT ke booking_slots DULU — unique index (court_id, date, slot) akan
     *    otomatis gagal kalau slot sudah ada → tidak mungkin double booking
     * 3. Baru buat record Booking setelah slot berhasil di-claim
     * 4. Cache availability langsung di-clear
     */
    public function execute(
        int $userId,
        int $courtId,
        string $date,
        array $slots,
        ?string $voucherCode = null,
        ?string $notes = null
    ): Booking {
        // Normalisasi slots
        $slots = array_values(array_unique($slots));
        sort($slots);

        if (empty($slots)) {
            throw new \RuntimeException('Pilih minimal satu jam untuk booking.');
        }

        // Hapus cache SEBELUM transaksi dimulai, agar request lain
        // yang berjalan paralel tidak dapat data stale dari cache
        $this->bookingService->clearAvailabilityCache($courtId, $date);

        return DB::transaction(function () use ($userId, $courtId, $date, $slots, $voucherCode, $notes) {

            // ------------------------------------------------------------------
            // STEP 1: Hitung harga
            // ------------------------------------------------------------------
            $pricing   = $this->bookingService->calculatePricing($courtId, $slots, $voucherCode);
            $qrCodeKey = 'PBDK-' . strtoupper(Str::random(10));

            // ------------------------------------------------------------------
            // STEP 2: Buat record Booking utama
            // ------------------------------------------------------------------
            $booking = Booking::create([
                'user_id'        => $userId,
                'court_id'       => $courtId,
                'date'           => $date,
                'slots'          => $slots,
                'total_price'    => $pricing['total_price'],
                'dp_amount'      => $pricing['dp_amount'],
                'payment_status' => 'pending',
                'status'         => 'pending',
                'qr_code'        => $qrCodeKey,
                'notes'          => $notes,
            ]);

            // ------------------------------------------------------------------
            // STEP 3: INSERT ke booking_slots — ini yang menjamin 100% tidak
            // double booking karena ada UNIQUE constraint di database.
            // SQLite akan serialize transaksi write, jadi hanya satu transaksi
            // yang bisa insert slot yang sama.
            // ------------------------------------------------------------------
            foreach ($slots as $slot) {
                try {
                    BookingSlot::create([
                        'booking_id' => $booking->id,
                        'court_id'   => $courtId,
                        'date'       => $date,
                        'slot'       => $slot,
                    ]);
                } catch (QueryException $e) {
                    // Unique constraint violation → slot sudah dipesan
                    throw new \RuntimeException(
                        "Slot jam {$slot} sudah dipesan oleh pengguna lain. Silakan pilih jam lain."
                    );
                }
            }

            // ------------------------------------------------------------------
            // STEP 4: Kurangi kuota voucher jika dipakai
            // ------------------------------------------------------------------
            if (!empty($pricing['voucher_id'])) {
                Voucher::where('id', $pricing['voucher_id'])->decrement('quota');
            }

            // ------------------------------------------------------------------
            // STEP 5: Hapus cache availability agar UI langsung ter-update
            // ------------------------------------------------------------------
            $this->bookingService->clearAvailabilityCache($courtId, $date);

            return $booking;
        });
    }
}
