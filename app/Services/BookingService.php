<?php

namespace App\Services;

use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Voucher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class BookingService
 * 
 * Menyediakan logika bisnis inti untuk pengecekan ketersediaan slot lapangan,
 * pengarsipan data pemesanan aktif, pengelolaan cache ketersediaan (availability cache),
 * validasi ketersediaan real-time saat transaksi, serta kalkulasi rincian harga
 * (subtotal, diskon voucher fixed/percentage/free hour, total harga, dan DP 50%).
 * 
 * @package App\Services
 */
class BookingService
{
    /** Status booking yang masih menahan slot. */
    public const HOLDING_STATUSES = ['pending', 'confirmed', 'completed'];

    /** Pembayaran gagal/refund -> slot dilepas. */
    public const RELEASED_PAYMENT_STATUSES = ['failed', 'refunded'];

    /**
     * Mengambil daftar Jam Operasional Lapangan.
     * 
     * @return array<int, string>
     */
    public function getOperationalSlots(): array
    {
        return [
            '10:00', '11:00', '12:00', '13:00', '14:00', '15:00',
            '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00',
        ];
    }

    /**
     * Memeriksa apakah suatu booking masih mengunci/menahan slot di kalender.
     * 
     * @param Booking $booking
     * @return bool
     */
    public function bookingHoldsSlots(Booking $booking): bool
    {
        // Jika status utama tidak termasuk holding statuses
        if (!in_array($booking->status, self::HOLDING_STATUSES, true)) {
            return false;
        }

        // Jika status pembayaran menunjukkan dilepas (failed atau refunded)
        if (in_array($booking->payment_status, self::RELEASED_PAYMENT_STATUSES, true)) {
            return false;
        }

        return true;
    }

    /**
     * Mengambil Daftar Jam yang Sudah Dipesan/Terblokir (Mendukung Cache).
     * 
     * @param int $courtId ID Lapangan
     * @param string $date Tanggal Sesi
     * @param bool $useCache Gunakan Cache (default true)
     * @return array{booked: array<int, string>, blocked: array<int, string>}
     */
    public function getBookedSlots(int $courtId, string $date, bool $useCache = true): array
    {
        // Real-time: selalu ambil langsung dari DB tanpa cache
        // Ini memastikan kalender selalu sinkron dan mencegah double booking
        return $this->resolveBookedSlots($courtId, $date);
    }

    /**
     * Mengambil Slot Terpesan/Terblokir Langsung dari Database.
     * 
     * @param int $courtId ID Lapangan
     * @param string $date Tanggal Sesi
     * @return array{booked: array<int, string>, blocked: array<int, string>}
     */
    protected function resolveBookedSlots(int $courtId, string $date): array
    {
        $booked = [];

        // 1. Ambil dari tabel index slot jika tabel booking_slots tersedia
        if ($this->bookingSlotsTableExists()) {
            $booked = array_merge($booked, $this->getBookedSlotsFromTable($courtId, $date));
        }

        // 2. Ambil dari kolom array JSON Booking (fallback kompatibilitas data lama)
        $booked = array_merge($booked, $this->getBookedSlotsFromBookingsJson($courtId, $date));

        // 3. Ambil slot terblokir pemeliharaan (Blocked Slot)
        $blocked = BlockedSlot::where('court_id', $courtId)
            ->whereDate('date', $date)
            ->get()
            ->pluck('slots')
            ->flatten()
            ->all();

        return [
            'booked'  => array_values(array_unique($booked)),
            'blocked' => array_values(array_unique($blocked)),
        ];
    }

    /**
     * Memeriksa keberadaan tabel `booking_slots` di database.
     * 
     * @return bool
     */
    protected function bookingSlotsTableExists(): bool
    {
        return DB::getSchemaBuilder()->hasTable('booking_slots');
    }

    /**
     * Mengambil slot terpesan dari tabel `booking_slots`.
     * 
     * @param int $courtId ID Lapangan
     * @param string $date Tanggal Sesi
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
     * Fallback: Mengambil slot dari kolom array JSON `bookings.slots`.
     * 
     * @param int $courtId ID Lapangan
     * @param string $date Tanggal Sesi
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
     * Mengambil Status Ketersediaan Sesi Per Jam (Untuk Widget Kalender/API).
     * 
     * Untuk tanggal hari ini, slot jam yang sudah berlalu (jam sudah lewat)
     * akan otomatis ditandai `is_past = true` dan `is_available = false`.
     * 
     * @param int $courtId ID Lapangan
     * @param string $date Tanggal Sesi (YYYY-MM-DD)
     * @return array
     */
    public function getAvailability(int $courtId, string $date): array
    {
        $court = Court::findOrFail($courtId);
        $slotsData = $this->getBookedSlots($courtId, $date, true);
        
        $booked  = $slotsData['booked'] ?? [];
        $blocked = $slotsData['blocked'] ?? [];

        $availability = [];
        $isActive = ($court->status === 'active');

        // Deteksi apakah tanggal yang dipilih adalah HARI INI
        // Gunakan timezone Asia/Makassar (WITA / UTC+8) agar sesuai dengan waktu lokal pengguna
        $now       = now()->timezone('Asia/Makassar');
        $isToday   = ($date === $now->toDateString());

        // Evaluasi status masing-masing jam sesi operasional
        foreach ($this->getOperationalSlots() as $slot) {
            $isBooked    = in_array($slot, $booked, true);
            $isBlocked   = in_array($slot, $blocked, true);

            // Cek apakah jam slot ini sudah lewat batas waktu (tersedia hingga 30 menit setelah jam mulai)
            // Format slot: "HH:MM"
            $slotHour = (int) explode(':', $slot)[0];
            $slotLimitTime = $now->copy()->setTime($slotHour, 30, 0);
            
            $isPast   = $isToday && $now->greaterThan($slotLimitTime);

            // Slot dapat dipilih hanya jika: lapangan aktif, tidak dipesan, tidak diblokir, dan belum lewat
            $isAvailable = $isActive && !$isBooked && !$isBlocked && !$isPast;

            $availability[] = [
                'slot'         => $slot,
                'is_available' => $isAvailable,
                'is_blocked'   => $isBlocked || !$isActive,
                'is_past'      => $isPast, // flag khusus untuk UI (warna abu-abu + no-click)
            ];
        }

        return $availability;
    }


    /**
     * Validasi Ketat Ketersediaan Slot Sebelum Melakukan Checkout / Reservasi.
     * 
     * Mengabaikan cache untuk memastikan slot 100% kosong.
     * 
     * @param int $courtId ID Lapangan
     * @param string $date Tanggal Sesi
     * @param array $slots Daftar Jam Sesi
     * @return void
     * @throws \RuntimeException
     */
    public function assertSlotsAvailable(int $courtId, string $date, array $slots): void
    {
        $court = Court::findOrFail($courtId);
        if ($court->status !== 'active') {
            throw new \RuntimeException("Lapangan ini sedang dalam pemeliharaan dan tidak dapat dipesan.");
        }

        $slots = array_values(array_unique($slots));
        $operational = $this->getOperationalSlots();

        // 1. Pastikan slot yang diminta masuk dalam jam operasional
        foreach ($slots as $slot) {
            if (!in_array($slot, $operational, true)) {
                throw new \RuntimeException("Jam {$slot} tidak tersedia untuk booking.");
            }
        }

        // 2. Validasi server-side: tolak slot yang sudah lewat untuk tanggal HARI INI
        $now      = now()->timezone('Asia/Makassar');
        $isToday  = ($date === $now->toDateString());
        if ($isToday) {
            $currentMinute = $now->hour * 60 + $now->minute;
            foreach ($slots as $slot) {
                $slotHour   = (int) explode(':', $slot)[0];
                $slotMinute = $slotHour * 60;
                if ($currentMinute >= $slotMinute + 30) {
                    throw new \RuntimeException("Jam {$slot} sudah lewat dan tidak dapat dipesan. Silakan pilih jam lain.");
                }
            }
        }

        // 3. Tolak jika tanggal yang dipilih adalah masa lalu
        if ($date < $now->toDateString()) {
            throw new \RuntimeException("Tidak dapat memesan lapangan untuk tanggal yang sudah lewat.");
        }

        // 4. Race-condition guard: kunci baris di DB dengan pessimistic lock
        // Siapapun yang meminta transaksi ini lebih dulu akan menang.
        // User kedua yang meminta slot yang sama di detik berbeda akan langsung ditolak.
        DB::transaction(function () use ($courtId, $date, $slots) {
            // Kunci baris booking_slots untuk court+date ini agar tidak ada insert paralel
            if ($this->bookingSlotsTableExists()) {
                // Lock seluruh baris yang relevan (baris yang sudah ada)
                BookingSlot::where('court_id', $courtId)
                    ->whereDate('date', $date)
                    ->lockForUpdate()
                    ->get();
            }

            // Cek ulang ketersediaan di dalam transaksi (bukan dari cache)
            $slotsData      = $this->resolveBookedSlots($courtId, $date);
            $booked         = $slotsData['booked']  ?? [];
            $blocked        = $slotsData['blocked'] ?? [];
            $allUnavailable = array_merge($booked, $blocked);

            foreach ($slots as $slot) {
                if (in_array($slot, $allUnavailable, true)) {
                    if (in_array($slot, $blocked, true)) {
                        throw new \RuntimeException("Jam {$slot} sedang dalam pemeliharaan. Silakan pilih jam lain.");
                    }
                    throw new \RuntimeException("Maaf, jam {$slot} baru saja dipesan oleh pengguna lain. Silakan pilih jam lain.");
                }
            }
        });
    }

    /**
     * Mengunci/Menyimpan Slot ke Tabel `booking_slots` Manual.
     * 
     * @param Booking $booking Record Booking
     * @param array $slots Jam Sesi
     * @return void
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
                'court_id'   => $booking->court_id,
                'date'       => $date,
                'slot'       => $slot,
            ]);
        }

        $this->clearAvailabilityCache($booking->court_id, $date);
    }

    /**
     * Melepas Slot Pemesanan Saat Reservasi Dibatalkan / Gagal Bayar.
     * 
     * @param Booking $booking
     * @return void
     */
    public function releaseSlotsForBooking(Booking $booking): void
    {
        $dateStr = $booking->date->format('Y-m-d');

        if (!$this->bookingSlotsTableExists()) {
            $this->clearAvailabilityCache($booking->court_id, $dateStr);
            return;
        }

        BookingSlot::where('booking_id', $booking->id)->delete();
        $this->clearAvailabilityCache($booking->court_id, $dateStr);
    }

    /**
     * Sinkronisasi Ulang Slot Pemesanan (Misal Pasca Migrasi / Pemulihan Data).
     * 
     * @param Booking $booking
     * @return void
     */
    public function syncSlotsForBooking(Booking $booking): void
    {
        $dateStr = $booking->date->format('Y-m-d');

        if (!$this->bookingSlotsTableExists() || !$this->bookingHoldsSlots($booking)) {
            $this->releaseSlotsForBooking($booking);
            return;
        }

        $slots = is_array($booking->slots) ? $booking->slots : [];

        // Bersihkan slot lama dan daftarkan kembali secara teratur
        BookingSlot::where('booking_id', $booking->id)->delete();

        foreach ($slots as $slot) {
            try {
                BookingSlot::create([
                    'booking_id' => $booking->id,
                    'court_id'   => $booking->court_id,
                    'date'       => $dateStr,
                    'slot'       => $slot,
                ]);
            } catch (\Illuminate\Database\QueryException) {
                // Abaikan jika ada konflik unik index saat sinkronisasi data rusak
            }
        }

        $this->clearAvailabilityCache($booking->court_id, $dateStr);
    }

    /**
     * Mengosongkan/Menghapus Cache Ketersediaan Lapangan.
     * 
     * @param int $courtId ID Lapangan
     * @param string $date Tanggal Sesi
     * @return void
     */
    public function clearAvailabilityCache(int $courtId, string $date): void
    {
        Cache::forget("court_{$courtId}_avail_{$date}");
        Cache::forget("court_{$courtId}_avail_v2_{$date}");
    }

    /**
     * Menghitung Rincian Harga Pemesanan, Diskon Voucher, dan Total Bayar.
     * 
     * Mendukung voucher jenis `fixed` (nominal tetap), `percentage` (persentase),
     * serta `free_hour` (gratis 1 jam).
     * 
     * @param int $courtId ID Lapangan
     * @param array $slots Jam Sesi yang dipilih
     * @param string|null $voucherCode Kode Voucher Promo (opsional)
     * @return array
     */
    public function calculatePricing(int $courtId, array $slots, ?string $voucherCode = null): array
    {
        $court = Court::findOrFail($courtId);
        $hourlyPrice = $court->price_per_hour;
        $slotCount = count($slots);
        $subtotal = $hourlyPrice * $slotCount;

        $discount = 0.00;
        $voucherId = null;

        // 1. Terapkan logika diskon voucher jika ada kode yang dimasukkan
        if ($voucherCode) {
            $voucher = Voucher::where('code', $voucherCode)->first();
            if ($voucher && $voucher->isValidFor($subtotal)) {
                $discount = $voucher->calculateDiscount($subtotal, $hourlyPrice);
                $voucherId = $voucher->id;
            }
        }

        // 2. Hitung total harga akhir (tidak boleh negatif) dan nominal DP 50%
        $totalPrice = max(0.00, $subtotal - $discount);
        $dpAmount = round($totalPrice * 0.50, 2);

        return [
            'court_id'     => $courtId,
            'hourly_price' => $hourlyPrice,
            'slot_count'   => $slotCount,
            'subtotal'     => $subtotal,
            'discount'     => $discount,
            'voucher_id'   => $voucherId,
            'total_price'  => $totalPrice,
            'dp_amount'    => $dpAmount,
        ];
    }
}

