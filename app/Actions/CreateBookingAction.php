<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Voucher;
use App\Services\BookingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class CreateBookingAction
 * 
 * Melayani pembuatan reservasi baru secara terisolasi dan transaksional.
 * Mengimplementasikan penguncian slot yang aman untuk mencegah *double-booking* 100%
 * baik pada driver MySQL maupun SQLite.
 * 
 * @package App\Actions
 */
class CreateBookingAction
{
    /**
     * Service untuk mengelola status ketersediaan lapangan.
     */
    protected BookingService $bookingService;

    /**
     * CreateBookingAction constructor.
     * 
     * @param BookingService $bookingService
     */
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Eksekusi Pembuatan Reservasi Baru Secara Aman (Anti Double-Booking).
     * 
     * Menjalankan rentetan aksi dalam database transaction, melakukan pencatatan slot
     * pada tabel `booking_slots` yang memiliki unique index constraint, mengurangi kuota voucher,
     * serta mengosongkan cache ketersediaan lapangan.
     * 
     * @param int $userId ID Pengguna
     * @param int $courtId ID Lapangan
     * @param string $date Tanggal Sesi (YYYY-MM-DD)
     * @param array $slots Jam Sesi yang dipilih
     * @param string|null $voucherCode Kode Voucher Promo (opsional)
     * @param string|null $notes Catatan Khusus Reservasi (opsional)
     * @return Booking
     * @throws \RuntimeException
     */
    public function execute(
        int $userId,
        int $courtId,
        string $date,
        array $slots,
        ?string $voucherCode = null,
        ?string $notes = null
    ): Booking {
        // 1. Normalisasi urutan slot jam sesi
        $slots = array_values(array_unique($slots));
        sort($slots);

        if (empty($slots)) {
            throw new \RuntimeException('Pilih minimal satu jam untuk booking.');
        }

        // 2. Hapus cache ketersediaan SEBELUM transaksi database dimulai.
        // Langkah ini penting agar request paralel lain tidak mendapatkan data stale/usang dari cache.
        $this->bookingService->clearAvailabilityCache($courtId, $date);

        $court = \App\Models\Court::findOrFail($courtId);
        if ($court->status !== 'active') {
            throw new \RuntimeException("Lapangan ini sedang dalam pemeliharaan dan tidak dapat dipesan.");
        }

        // 3. Jalankan seluruh proses penulisan data dalam satu transaksi database (DB Transaction)
        return DB::transaction(function () use ($userId, $courtId, $date, $slots, $voucherCode, $notes) {

            // --- STEP 1: Hitung rincian harga akhir ---
            $pricing   = $this->bookingService->calculatePricing($courtId, $slots, $voucherCode);
            $qrCodeKey = 'PBDK-' . strtoupper(Str::random(10));

            // --- STEP 2: Buat data reservasi utama ---
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

            // --- STEP 3: Catat slot sesi ke tabel `booking_slots` ---
            // Ini adalah pilar utama pelindung double-booking. Kolom index unik (court_id, date, slot)
            // di database akan otomatis melempar QueryException jika slot telah terisi sebelumnya.
            foreach ($slots as $slot) {
                try {
                    BookingSlot::create([
                        'booking_id' => $booking->id,
                        'court_id'   => $courtId,
                        'date'       => $date,
                        'slot'       => $slot,
                    ]);
                } catch (QueryException $e) {
                    // Terjadi bentrokan unik index constraint -> slot telah dipesan pengguna lain
                    throw new \RuntimeException(
                        "Slot jam {$slot} sudah dipesan oleh pengguna lain. Silakan pilih jam lain."
                    );
                }
            }

            // --- STEP 4: Kurangi sisa kuota penggunaan voucher promo ---
            if (!empty($pricing['voucher_id'])) {
                Voucher::where('id', $pricing['voucher_id'])->decrement('quota');
            }

            // --- STEP 5: Hapus kembali cache ketersediaan pasca penulisan sukses ---
            $this->bookingService->clearAvailabilityCache($courtId, $date);

            return $booking;
        });
    }
}

