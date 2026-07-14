<?php

namespace App\Http\Controllers;

use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Voucher;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class AdminController
 * 
 * Mengelola fungsionalitas panel admin (back-office) meliputi visualisasi analitik,
 * CRUD lapangan, unggah foto, penjadwalan pemeliharaan slot (blocked slots),
 * verifikasi pembayaran manual, pembatalan/refund transaksi, pengelolaan voucher promo,
 * audit activity log, serta ekspor laporan pendapatan dalam format PDF & Excel/CSV.
 * 
 * @package App\Http\Controllers
 */
class AdminController extends Controller
{
    /**
     * Service ketersediaan/operasional booking.
     */
    protected BookingService $bookingService;

    /**
     * Service transaksi dan refund wallet.
     */
    protected PaymentService $paymentService;

    /**
     * AdminController constructor.
     * 
     * @param BookingService $bookingService
     * @param PaymentService $paymentService
     */
    public function __construct(BookingService $bookingService, PaymentService $paymentService)
    {
        $this->bookingService = $bookingService;
        $this->paymentService = $paymentService;
    }

    /**
     * Menampilkan Dashboard Analytics Panel Utama.
     * 
     * Menghitung rangkuman statistik real-time, grafik bulanan pendapatan tahun 2026,
     * serta distribusi tipe lapangan aktif. Mendukung respons AJAX untuk pemuatan dinamis.
     * 
     * @param Request $request
     * @return JsonResponse|View
     */
    public function index(Request $request): JsonResponse|View
    {
        // 1. Statistik ringkas: total reservasi keseluruhan
        $totalBookingsCount = Booking::count();

        // 2. Pendapatan hari ini yang sudah terbayar (lunas)
        $todayRevenue = Booking::where('payment_status', 'paid')
            ->whereDate('updated_at', today())
            ->sum('total_price');

        // 3. Jumlah lapangan aktif
        $activeCourtsCount = Court::where('status', 'active')->count();
        
        // 4. Deteksi Lapangan Terpopuler berdasarkan frekuensi reservasi
        $popularCourtName = 'Belum ada data';
        $popular = Booking::select('court_id', DB::raw('count(*) as count'))
            ->groupBy('court_id')
            ->orderBy('count', 'desc')
            ->first();

        if ($popular) {
            $court = Court::find($popular->court_id);
            if ($court) {
                $popularCourtName = $court->name . " ({$popular->count} kali)";
            }
        }

        // 5. Data Chart Bulanan untuk Pendapatan Tahun 2026
        // Mendukung multi-database driver (SQLite untuk lokal & MySQL untuk produksi)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthRaw = $isSqlite ? 'strftime("%m", date)' : 'MONTH(date)';

        $monthlyRevenue = Booking::select(
            DB::raw("$monthRaw as month"),
            DB::raw('SUM(total_price) as sum')
        )
        ->where('payment_status', 'paid')
        ->whereYear('date', 2026)
        ->groupBy('month')
        ->pluck('sum', 'month')
        ->toArray();

        // Mengisi nilai default 0 untuk bulan 1-12
        $revenueChartData = array_fill(1, 12, 0);
        foreach ($monthlyRevenue as $month => $sum) {
            $revenueChartData[(int) $month] = (float) $sum;
        }

        // 6. Distribusi tipe lapangan (Indoor vs Outdoor) untuk diagram donat
        $typeDistribution = Court::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $courts = Court::orderBy('name')->get();

        // 7. Jika diakses via AJAX, kembalikan respons format JSON untuk pembaruan cepat widget dashboard
        if ($request->ajax() || $request->query('ajax')) {
            return response()->json([
                'totalBookingsCount' => number_format($totalBookingsCount),
                'todayRevenue'       => 'Rp ' . number_format($todayRevenue/1000, 0) . 'K',
                'activeCourtsCount'  => $activeCourtsCount,
                'popularCourtName'   => $popularCourtName,
                'revenueChartData'   => array_values($revenueChartData),
                'typeDistribution'   => $typeDistribution,
                'tableHtml'          => view('admin.partials.courts_table', compact('courts'))->render()
            ]);
        }

        // 8. Pemuatan biasa: render file view index dashboard admin
        return view('admin.index', compact(
            'totalBookingsCount',
            'todayRevenue',
            'activeCourtsCount',
            'popularCourtName',
            'revenueChartData',
            'typeDistribution',
            'courts'
        ));
    }

    /**
     * Menampilkan Halaman Tambah Lapangan Baru.
     * 
     * @return View
     */
    public function create(): View
    {
        return view('admin.courts.create');
    }

    /**
     * Menyimpan Lapangan Baru ke Database.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi input data lapangan baru (termasuk foto opsional multi-upload)
        $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|string',
            'price_per_hour' => 'required|numeric|min:0',
            'description'    => 'nullable|string',
            'photos'         => 'nullable|array',
            'photos.*'       => 'image|mimes:jpg,jpeg,png,webp|max:3072', // maks 3MB per foto
        ]);

        // 2. Proses unggahan foto lapangan ke storage
        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photoFile) {
                // Simpan ke folder storage/app/public/courts
                $path = $photoFile->store('courts', 'public');
                $photoPaths[] = 'storage/' . $path;
            }
        }

        // 3. Simpan data lapangan ke database
        Court::create([
            'name'           => $request->name,
            'type'           => $request->type,
            'price_per_hour' => $request->price_per_hour,
            'description'    => $request->description,
            'status'         => 'active',
            'photos'         => $photoPaths,
        ]);

        // 4. Catat aktivitas audit log jika library Spatie ActivityLog terpasang
        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            activity()->log("Menambahkan lapangan baru: {$request->name}");
        }

        return redirect()->route('admin.index')->with('success', 'Lapangan padel berhasil dibuat!');
    }

    /**
     * Menampilkan Halaman Edit Lapangan.
     * 
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $court = Court::findOrFail($id);
        return view('admin.courts.edit', compact('court'));
    }

    /**
     * Memperbarui Lapangan di Database.
     * 
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $court = Court::findOrFail($id);

        // 1. Validasi pembaruan data lapangan (termasuk foto opsional)
        $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|string',
            'price_per_hour' => 'required|numeric|min:0',
            'description'    => 'nullable|string',
            'status'         => 'required|string|in:active,maintenance',
            'photos'         => 'nullable|array',
            'photos.*'       => 'image|mimes:jpg,jpeg,png,webp|max:3072', // maks 3MB per foto
            'photo_remove'   => 'nullable|array', // daftar path foto yang ingin dihapus
            'photo_remove.*' => 'nullable|string',
        ]);

        // 2. Mulai dari daftar foto yang sudah ada di database
        $currentPhotos = $court->photos ?? [];

        // 3. Hapus foto-foto yang dicentang oleh admin untuk dihapus
        if ($request->has('photo_remove')) {
            $toRemove = $request->input('photo_remove', []);
            foreach ($toRemove as $removePath) {
                // Hapus file fisik dari storage jika path berawalan 'storage/'
                if (str_starts_with($removePath, 'storage/')) {
                    $diskPath = str_replace('storage/', '', $removePath);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($diskPath);
                }
                // Hapus dari array referensi
                $currentPhotos = array_filter($currentPhotos, fn($p) => $p !== $removePath);
            }
            $currentPhotos = array_values($currentPhotos);
        }

        // 4. Tambahkan foto-foto baru yang diunggah admin
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photoFile) {
                $path = $photoFile->store('courts', 'public');
                $currentPhotos[] = 'storage/' . $path;
            }
        }

        // 5. Eksekusi pembaruan record database termasuk kolom photos
        $court->update([
            'name'           => $request->name,
            'type'           => $request->type,
            'price_per_hour' => $request->price_per_hour,
            'description'    => $request->description,
            'status'         => $request->status,
            'photos'         => $currentPhotos,
        ]);

        // 6. Catat aktivitas audit log
        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            activity()->log("Memperbarui lapangan: {$court->name}");
        }

        return redirect()->route('admin.index')->with('success', 'Detail lapangan berhasil diperbarui!');
    }

    /**
     * Menghapus (Soft Delete) Lapangan.
     * 
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $court = Court::findOrFail($id);
        $court->delete();

        // Catat ke audit log
        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            activity()->log("Menghapus lapangan: {$court->name}");
        }

        return redirect()->route('admin.index')->with('success', 'Lapangan padel berhasil dinonaktifkan (soft delete).');
    }

    /**
     * Mengunggah Galeri Foto Lapangan (Multi-upload).
     * 
     * Menampung unggahan foto dari library Filepond/Dropzone di frontend
     * dan menyimpannya di folder disk public/courts.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function uploadCourtPhotos(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'photos'   => 'required|array',
            'photos.*' => 'image|max:3072' // limit maksimum 3MB per berkas foto
        ]);

        $court = Court::findOrFail($id);
        $currentPhotos = $court->photos ?? [];

        // Iterasi berkas foto yang masuk dan simpan ke disk public
        foreach ($request->file('photos') as $photoFile) {
            $path = $photoFile->store('courts', 'public');
            $currentPhotos[] = 'storage/' . $path;
        }

        // Simpan tautan foto terbaru ke data lapangan
        $court->update(['photos' => $currentPhotos]);

        return response()->json([
            'success' => true,
            'photos'  => $currentPhotos
        ]);
    }

    /**
     * Menampilkan Daftar Slot Terblokir untuk Pemeliharaan Lapangan.
     * 
     * @param Request $request
     * @return JsonResponse|View
     */
    public function blockedSlotsIndex(Request $request): JsonResponse|View
    {
        $courts = Court::all();
        $blockedSlots = BlockedSlot::with('court')->orderBy('date', 'desc')->get();
        $slots = $this->bookingService->getOperationalSlots();

        // AJAX response untuk reload tabel secara asinkronus
        if ($request->ajax() || $request->query('ajax')) {
            return response()->json([
                'tableHtml' => view('admin.partials.blocked_slots_table', compact('blockedSlots'))->render()
            ]);
        }

        return view('admin.blocked_slots', compact('courts', 'blockedSlots', 'slots'));
    }

    /**
     * Menyimpan Pemblokiran Slot Baru (Mencegah Reservasi Sementara).
     * 
     * Digunakan apabila ada jadwal maintenance rutin atau acara khusus di lapangan padel.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeBlockedSlot(Request $request): RedirectResponse
    {
        // 1. Validasi parameter input slot blokir
        $request->validate([
            'court_id' => 'required|exists:courts,id',
            'date'     => 'required|date',
            'slots'    => 'required|array',
            'reason'   => 'required|string|max:255',
        ]);

        // 2. Simpan data slot blokir
        BlockedSlot::create([
            'court_id' => $request->court_id,
            'date'     => $request->date,
            'slots'    => $request->slots,
            'reason'   => $request->reason,
        ]);

        // 3. Hapus cache ketersediaan slot agar UI frontend langsung menampilkan status tidak tersedia
        $this->bookingService->clearAvailabilityCache($request->court_id, $request->date);

        return back()->with('success', 'Slot lapangan berhasil diblokir untuk pemeliharaan/acara.');
    }

    /**
     * Menghapus Pemblokiran Slot (Membuka Lapangan Kembali).
     * 
     * @param int $id
     * @return RedirectResponse
     */
    public function deleteBlockedSlot(int $id): RedirectResponse
    {
        $slot = BlockedSlot::findOrFail($id);
        $courtId = $slot->court_id;
        $date = $slot->date->toDateString();
        
        $slot->delete();

        // Hapus cache ketersediaan pasca pembukaan blokir slot
        $this->bookingService->clearAvailabilityCache($courtId, $date);

        return back()->with('success', 'Pemblokiran slot berhasil dibuka kembali.');
    }

    /**
     * Menampilkan Semua Daftar Reservasi Pengguna.
     * 
     * Menyajikan data booking secara berurutan dan membaginya dalam paginasi.
     * 
     * @param Request $request
     * @return JsonResponse|View
     */
    public function bookingsIndex(Request $request): JsonResponse|View
    {
        $bookings = Booking::with(['user', 'court'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Dukungan pemuatan asinkronus via AJAX
        if ($request->ajax() || $request->query('ajax')) {
            return response()->json([
                'tableHtml'      => view('admin.partials.bookings_table', compact('bookings'))->render(),
                'paginationHtml' => $bookings->links('pagination::bootstrap-5')->render()
            ]);
        }

        return view('admin.bookings', compact('bookings'));
    }

    /**
     * Mengubah Status Reservasi Secara Manual oleh Admin.
     * 
     * Dapat digunakan untuk menyelesaikan sesi manual, membatalkan sesi,
     * serta memberikan poin loyalty reward kepada pengguna saat sesi berhasil diselesaikan.
     * 
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function updateBookingStatus(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'status' => 'required|string|in:pending,confirmed,completed,cancelled',
        ]);

        $booking = Booking::with('user', 'court')->findOrFail($id);
        
        // Jika status diubah menjadi completed, kirim tanda terima e-tiket serta berikan poin loyalty reward
        if ($request->status === 'completed' && $booking->status !== 'completed') {
            app(\App\Services\NotificationService::class)->sendBookingReceipt($booking);

            // Skema Loyalty Points: otomatis memberikan poin loyalty setelah status berubah menjadi completed
            $pointsEarned = 1;
            $user = $booking->user;
            if ($user) {
                // Pastikan poin hanya diberikan satu kali saja untuk menghindari kecurangan/duplikasi
                $pointsGranted = \App\Models\LoyaltyPoint::where('booking_id', $booking->id)
                    ->where('type', 'earn')
                    ->exists();

                if (!$pointsGranted) {
                    $user->increment('points', $pointsEarned);

                    \App\Models\LoyaltyPoint::create([
                        'user_id'     => $user->id,
                        'booking_id'  => $booking->id,
                        'points'      => $pointsEarned,
                        'type'        => 'earn',
                        'description' => 'Sesi Selesai (Booking #' . $booking->id . ')'
                    ]);
                }
            }
        }

        $booking->update(['status' => $request->status]);

        return back()->with('success', 'Status reservasi berhasil diubah ke: ' . strtoupper($request->status));
    }

    /**
     * Menyetujui Bukti Transfer Pembayaran Manual.
     * 
     * Mengubah status pembayaran menjadi "paid" dan status reservasi menjadi "confirmed",
     * serta memicu pengiriman notifikasi detail tiket via Email & WhatsApp.
     * 
     * @param int $id
     * @return RedirectResponse
     */
    public function approvePayment(int $id): RedirectResponse
    {
        $booking = Booking::findOrFail($id);

        // Pastikan pesanan sedang menunggu persetujuan (awaiting_approval)
        if ($booking->payment_status !== 'awaiting_approval') {
            return back()->withErrors(['error' => 'Pesanan ini tidak sedang menunggu persetujuan resi.']);
        }

        // Set lunas & konfirmasi booking
        $booking->update([
            'payment_status' => 'paid',
            'status'         => 'confirmed'
        ]);

        // Jalankan pengiriman tanda terima cetak tiket digital (Email & WhatsApp)
        try {
            app(\App\Services\NotificationService::class)->sendBookingReceipt($booking);
        } catch (\Exception $e) {
            Log::error("Failed to send booking receipt after manual approval: " . $e->getMessage());
        }

        return back()->with('success', 'Pembayaran berhasil disetujui! Booking kini aktif.');
    }

    /**
     * Menolak Bukti Transfer Pembayaran Manual.
     * 
     * Membatalkan booking dan mengeset status pembayaran menjadi "failed" jika resi tidak valid.
     * 
     * @param int $id
     * @return RedirectResponse
     */
    public function rejectPayment(int $id): RedirectResponse
    {
        $booking = Booking::findOrFail($id);

        if ($booking->payment_status !== 'awaiting_approval') {
            return back()->withErrors(['error' => 'Pesanan ini tidak sedang menunggu persetujuan resi.']);
        }

        // Tandai gagal & dibatalkan
        $booking->update([
            'payment_status' => 'failed',
            'status'         => 'cancelled'
        ]);

        return back()->with('success', 'Pembayaran ditolak. Booking dibatalkan.');
    }

    /**
     * Melakukan Refund Pembayaran Secara Manual ke Saldo Wallet Pengguna.
     * 
     * Digunakan apabila ada pembatalan resmi oleh pihak pengelola lapangan padel.
     * 
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function manualRefund(Request $request, int $id): RedirectResponse
    {
        $booking = Booking::findOrFail($id);

        // Hanya booking lunas atau bayar DP (partial) yang dapat direfund
        if ($booking->payment_status !== 'paid' && $booking->payment_status !== 'partial') {
            return back()->withErrors(['error' => 'Reservasi belum dibayar, tidak bisa refund.']);
        }

        $refundAmount = $booking->total_price;

        // Proses mutasi DB secara transaksional
        DB::transaction(function () use ($booking, $refundAmount) {
            // Update status booking
            $booking->update([
                'payment_status' => 'refunded',
                'status'         => 'cancelled'
            ]);

            // Kreditkan uang kembali ke saldo digital wallet pengguna
            $this->paymentService->refundToWallet($booking, $refundAmount, 'Pembatalan Sesi oleh Admin');
        });

        return back()->with('success', 'Reservasi berhasil dibatalkan dan dana dikreditkan ke saldo wallet user.');
    }

    /**
     * Menampilkan Daftar Voucher Promo.
     * 
     * @return View
     */
    public function vouchersIndex(): View
    {
        $vouchers = Voucher::orderBy('expired_at', 'desc')->get();
        return view('admin.vouchers', compact('vouchers'));
    }

    /**
     * Menyimpan Voucher Promo Baru.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeVoucher(Request $request): RedirectResponse
    {
        // 1. Validasi parameter voucher baru
        $request->validate([
            'code'        => 'required|string|unique:vouchers,code|max:50',
            'type'        => 'required|string|in:fixed,percentage',
            'value'       => 'required|numeric|min:0',
            'min_booking' => 'required|numeric|min:0',
            'quota'       => 'required|integer|min:1',
            'expired_at'  => 'required|date|after_or_equal:today',
        ]);

        // 2. Normalisasi kode menjadi huruf besar (case-insensitive)
        $request->merge(['code' => strtoupper(trim($request->code))]);

        // 3. Simpan data voucher baru ke DB
        Voucher::create($request->all());

        return back()->with('success', 'Voucher baru berhasil didaftarkan!');
    }

    /**
     * Menghapus (Soft Delete) Voucher Promo.
     * 
     * @param int $id
     * @return RedirectResponse
     */
    public function deleteVoucher(int $id): RedirectResponse
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();
        return back()->with('success', 'Voucher berhasil dinonaktifkan.');
    }

    /**
     * Menampilkan Log Aktivitas Audit Lapangan (Spatie Activity Log).
     * 
     * @param Request $request
     * @return JsonResponse|View
     */
    public function activityLogs(Request $request): JsonResponse|View
    {
        $logs = [];
        // Pastikan tabel dan class activitylog terdaftar di aplikasi untuk menghindari error database
        if (class_exists(\Spatie\Activitylog\Models\Activity::class) && \Illuminate\Support\Facades\Schema::hasTable('activity_log')) {
            $logs = \Spatie\Activitylog\Models\Activity::with('causer')->orderBy('created_at', 'desc')->paginate(20);
        }

        // AJAX response untuk paginasi asinkronus log audit
        if ($request->ajax() || $request->query('ajax')) {
            $paginationHtml = '';
            if (is_object($logs) && method_exists($logs, 'links')) {
                $paginationHtml = $logs->links('pagination::bootstrap-5')->render();
            }
            return response()->json([
                'tableHtml'      => view('admin.partials.logs_table', compact('logs'))->render(),
                'paginationHtml' => $paginationHtml
            ]);
        }

        return view('admin.logs', compact('logs'));
    }

    /**
     * Menampilkan Log Percobaan Login Berdasarkan IP Address Pengguna.
     * 
     * @param Request $request
     * @return View
     */
    public function loginLogs(Request $request): View
    {
        $loginLogs = \App\Models\LoginLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.login_logs', compact('loginLogs'));
    }

    /**
     * Menampilkan Halaman Dashboard Pengecekan Laporan Finansial.
     * 
     * @return View
     */
    public function reportsIndex(): View
    {
        return view('admin.reports');
    }

    /**
     * Mengekspor Laporan Pendapatan Berbentuk PDF.
     * 
     * Menggunakan library DomPDF jika terinstal, atau menampilkan printer-friendly HTML table jika tidak tersedia.
     * 
     * @param Request $request
     * @return mixed
     */
    public function exportPdf(Request $request): mixed
    {
        $bookings = Booking::with(['user', 'court'])
            ->where('payment_status', 'paid')
            ->get();

        $data = [
            'title'    => 'Laporan Pendapatan PadelBook ' . date('Y'),
            'date'     => date('d-m-Y H:i'),
            'bookings' => $bookings,
            'total'    => $bookings->sum('total_price')
        ];

        // Jika DomPDF terdaftar, langsung tawarkan unduhan berkas PDF
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.pdf', $data);
            return $pdf->download('Laporan-Pendapatan-PadelBook.pdf');
        }

        // Tampilan cetak responsif printer-friendly layout (fallback)
        return view('admin.reports.pdf', $data);
    }

    /**
     * Mengekspor Laporan Pendapatan Berbentuk File CSV (Excel compatible) via Data Stream.
     * 
     * @return StreamedResponse
     */
    public function exportExcel(): StreamedResponse
    {
        $bookings = Booking::with(['user', 'court'])->where('payment_status', 'paid')->get();

        $csvFileName = 'Laporan-Pendapatan-PadelBook-' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Kolom header CSV
        $columns = ['ID Booking', 'Nama Member', 'Nama Lapangan', 'Tanggal', 'Slot Jam', 'Total Harga', 'Metode Pembayaran'];

        // Callback asinkronus stream download hemat memori RAM
        $callback = function() use($bookings, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($bookings as $booking) {
                fputcsv($file, [
                    $booking->id,
                    $booking->user->name,
                    $booking->court->name,
                    $booking->date->format('Y-m-d'),
                    implode(', ', $booking->slots),
                    $booking->total_price,
                    $booking->payment_method
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

