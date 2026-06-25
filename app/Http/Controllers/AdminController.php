<?php

namespace App\Http\Controllers;

use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Voucher;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    protected BookingService $bookingService;
    protected PaymentService $paymentService;

    public function __construct(BookingService $bookingService, PaymentService $paymentService)
    {
        $this->bookingService = $bookingService;
        $this->paymentService = $paymentService;
    }

    /**
     * Dashboard Analytics metrics panel.
     */
    public function index(Request $request)
    {
        // Statistics summaries
        $totalBookingsCount = Booking::count();
        $todayRevenue = Booking::where('payment_status', 'paid')
            ->whereDate('updated_at', today())
            ->sum('total_price');

        $activeCourtsCount = Court::where('status', 'active')->count();
        
        // Popular court logic based on reservation counts
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

        // Charts: monthly booking statistics for 2026
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

        $revenueChartData = array_fill(1, 12, 0);
        foreach ($monthlyRevenue as $month => $sum) {
            $revenueChartData[(int) $month] = (float) $sum;
        }

        // Donut: types distribution
        $typeDistribution = Court::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $courts = Court::orderBy('name')->get();

        if ($request->ajax() || $request->query('ajax')) {
            return response()->json([
                'totalBookingsCount' => number_format($totalBookingsCount),
                'todayRevenue' => 'Rp ' . number_format($todayRevenue/1000, 0) . 'K',
                'activeCourtsCount' => $activeCourtsCount,
                'popularCourtName' => $popularCourtName,
                'revenueChartData' => array_values($revenueChartData),
                'typeDistribution' => $typeDistribution,
                'tableHtml' => view('admin.partials.courts_table', compact('courts'))->render()
            ]);
        }

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

    // --- Court CRUD Methods ---

    public function create()
    {
        return view('admin.courts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'price_per_hour' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        Court::create([
            'name' => $request->name,
            'type' => $request->type,
            'price_per_hour' => $request->price_per_hour,
            'description' => $request->description,
            'status' => 'active',
            'photos' => [],
        ]);

        // Spatie activitylog helper if package loaded
        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            activity()->log("Menambahkan lapangan baru: {$request->name}");
        }

        return redirect()->route('admin.index')->with('success', 'Lapangan padel berhasil dibuat!');
    }

    public function edit(int $id)
    {
        $court = Court::findOrFail($id);
        return view('admin.courts.edit', compact('court'));
    }

    public function update(Request $request, int $id)
    {
        $court = Court::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'price_per_hour' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|string|in:active,maintenance',
        ]);

        $court->update([
            'name' => $request->name,
            'type' => $request->type,
            'price_per_hour' => $request->price_per_hour,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            activity()->log("Memperbarui lapangan: {$court->name}");
        }

        return redirect()->route('admin.index')->with('success', 'Detail lapangan berhasil diperbarui!');
    }

    public function destroy(int $id)
    {
        $court = Court::findOrFail($id);
        $court->delete();

        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            activity()->log("Menghapus lapangan: {$court->name}");
        }

        return redirect()->route('admin.index')->with('success', 'Lapangan padel berhasil dinonaktifkan (soft delete).');
    }

    /**
     * Filepond/Dropzone Multi-upload photos.
     */
    public function uploadCourtPhotos(Request $request, int $id)
    {
        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'image|max:3072'
        ]);

        $court = Court::findOrFail($id);
        $currentPhotos = $court->photos ?? [];

        foreach ($request->file('photos') as $photoFile) {
            $path = $photoFile->store('courts', 'public');
            $currentPhotos[] = 'storage/' . $path;
        }

        $court->update(['photos' => $currentPhotos]);

        return response()->json([
            'success' => true,
            'photos' => $currentPhotos
        ]);
    }

    // --- Blocked slots (Court Maintenance scheduling) ---

    public function blockedSlotsIndex(Request $request)
    {
        $courts = Court::all();
        $blockedSlots = BlockedSlot::with('court')->orderBy('date', 'desc')->get();
        $slots = $this->bookingService->getOperationalSlots();

        if ($request->ajax() || $request->query('ajax')) {
            return response()->json([
                'tableHtml' => view('admin.partials.blocked_slots_table', compact('blockedSlots'))->render()
            ]);
        }

        return view('admin.blocked_slots', compact('courts', 'blockedSlots', 'slots'));
    }

    public function storeBlockedSlot(Request $request)
    {
        $request->validate([
            'court_id' => 'required|exists:courts,id',
            'date' => 'required|date',
            'slots' => 'required|array',
            'reason' => 'required|string|max:255',
        ]);

        BlockedSlot::create([
            'court_id' => $request->court_id,
            'date' => $request->date,
            'slots' => $request->slots,
            'reason' => $request->reason,
        ]);

        // Clear slot cache
        $this->bookingService->clearAvailabilityCache($request->court_id, $request->date);

        return back()->with('success', 'Slot lapangan berhasil diblokir untuk pemeliharaan/acara.');
    }

    public function deleteBlockedSlot(int $id)
    {
        $slot = BlockedSlot::findOrFail($id);
        $courtId = $slot->court_id;
        $date = $slot->date->toDateString();
        
        $slot->delete();

        // Clear slot cache
        $this->bookingService->clearAvailabilityCache($courtId, $date);

        return back()->with('success', 'Pemblokiran slot berhasil dibuka kembali.');
    }

    // --- Kelola bookings (Overrides / Manual changes) ---

    public function bookingsIndex(Request $request)
    {
        $bookings = Booking::with(['user', 'court'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        if ($request->ajax() || $request->query('ajax')) {
            return response()->json([
                'tableHtml' => view('admin.partials.bookings_table', compact('bookings'))->render(),
                'paginationHtml' => $bookings->links('pagination::bootstrap-5')->render()
            ]);
        }

        return view('admin.bookings', compact('bookings'));
    }

    public function updateBookingStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,confirmed,completed,cancelled',
        ]);

        $booking = Booking::with('user', 'court')->findOrFail($id);
        
        // If status changed to completed, send the receipt
        if ($request->status === 'completed' && $booking->status !== 'completed') {
            app(\App\Services\NotificationService::class)->sendBookingReceipt($booking);

            // Grant loyalty points automatically after admin completes the transaction
            $pointsEarned = 1;
            $user = $booking->user;
            if ($user) {
                // Ensure points are only granted once per booking
                $pointsGranted = \App\Models\LoyaltyPoint::where('booking_id', $booking->id)
                    ->where('type', 'earn')
                    ->exists();

                if (!$pointsGranted) {
                    $user->increment('points', $pointsEarned);

                    \App\Models\LoyaltyPoint::create([
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                        'points' => $pointsEarned,
                        'type' => 'earn',
                        'description' => 'Sesi Selesai (Booking #' . $booking->id . ')'
                    ]);
                }
            }
        }

        $booking->update(['status' => $request->status]);

        return back()->with('success', 'Status reservasi berhasil diubah ke: ' . strtoupper($request->status));
    }

    public function approvePayment(int $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->payment_status !== 'awaiting_approval') {
            return back()->withErrors(['error' => 'Pesanan ini tidak sedang menunggu persetujuan resi.']);
        }

        $booking->update([
            'payment_status' => 'paid',
            'status' => 'confirmed'
        ]);

        // Send booking receipt (Email and WhatsApp) upon approval
        try {
            app(\App\Services\NotificationService::class)->sendBookingReceipt($booking);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send booking receipt after manual approval: " . $e->getMessage());
        }

        return back()->with('success', 'Pembayaran berhasil disetujui! Booking kini aktif.');
    }

    public function rejectPayment(int $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->payment_status !== 'awaiting_approval') {
            return back()->withErrors(['error' => 'Pesanan ini tidak sedang menunggu persetujuan resi.']);
        }

        $booking->update([
            'payment_status' => 'failed',
            'status' => 'cancelled'
        ]);

        return back()->with('success', 'Pembayaran ditolak. Booking dibatalkan.');
    }

    public function manualRefund(Request $request, int $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->payment_status !== 'paid' && $booking->payment_status !== 'partial') {
            return back()->withErrors(['error' => 'Reservasi belum dibayar, tidak bisa refund.']);
        }

        $refundAmount = $booking->total_price;

        DB::transaction(function () use ($booking, $refundAmount) {
            // Update booking
            $booking->update([
                'payment_status' => 'refunded',
                'status' => 'cancelled'
            ]);

            // Credit refund to user's wallet
            $this->paymentService->refundToWallet($booking, $refundAmount, 'Pembatalan Sesi oleh Admin');
        });

        return back()->with('success', 'Reservasi berhasil dibatalkan dan dana dikreditkan ke saldo wallet user.');
    }

    // --- Vouchers & Promo ---

    public function vouchersIndex()
    {
        $vouchers = Voucher::orderBy('expired_at', 'desc')->get();
        return view('admin.vouchers', compact('vouchers'));
    }

    public function storeVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:vouchers,code|max:50',
            'type' => 'required|string|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'min_booking' => 'required|numeric|min:0',
            'quota' => 'required|integer|min:1',
            'expired_at' => 'required|date|after_or_equal:today',
        ]);

        // Normalize code to uppercase to ensure case‑insensitive usage
        $request->merge(['code' => strtoupper(trim($request->code))]);

        Voucher::create($request->all());

        return back()->with('success', 'Voucher baru berhasil didaftarkan!');
    }

    public function deleteVoucher(int $id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();
        return back()->with('success', 'Voucher berhasil dinonaktifkan.');
    }

    // --- Activity Logs ---

    public function activityLogs(Request $request)
    {
        // Fetch audit logs (supports fallback if spatie/laravel-activitylog is empty or migration not run)
        $logs = [];
        if (class_exists(\Spatie\Activitylog\Models\Activity::class) && \Illuminate\Support\Facades\Schema::hasTable('activity_log')) {
            $logs = \Spatie\Activitylog\Models\Activity::with('causer')->orderBy('created_at', 'desc')->paginate(20);
        }

        if ($request->ajax() || $request->query('ajax')) {
            $paginationHtml = '';
            if (is_object($logs) && method_exists($logs, 'links')) {
                $paginationHtml = $logs->links('pagination::bootstrap-5')->render();
            }
            return response()->json([
                'tableHtml' => view('admin.partials.logs_table', compact('logs'))->render(),
                'paginationHtml' => $paginationHtml
            ]);
        }

        return view('admin.logs', compact('logs'));
    }

    public function loginLogs(Request $request)
    {
        $loginLogs = \App\Models\LoginLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.login_logs', compact('loginLogs'));
    }

    // --- Reports and Exports ---

    public function reportsIndex()
    {
        return view('admin.reports');
    }

    public function exportPdf(Request $request)
    {
        $bookings = Booking::with(['user', 'court'])
            ->where('payment_status', 'paid')
            ->get();

        $data = [
            'title' => 'Laporan Pendapatan PadelBook ' . date('Y'),
            'date' => date('d-m-Y H:i'),
            'bookings' => $bookings,
            'total' => $bookings->sum('total_price')
        ];

        // Barryvdh Dompdf fallback if packages not loaded, streams clean table
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.pdf', $data);
            return $pdf->download('Laporan-Pendapatan-PadelBook.pdf');
        }

        // Beautiful printer-friendly fallback layout
        return view('admin.reports.pdf', $data);
    }

    public function exportExcel()
    {
        $bookings = Booking::with(['user', 'court'])->where('payment_status', 'paid')->get();

        // High quality CSV download stream out-of-the-box
        $csvFileName = 'Laporan-Pendapatan-PadelBook-' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID Booking', 'Nama Member', 'Nama Lapangan', 'Tanggal', 'Slot Jam', 'Total Harga', 'Metode Pembayaran'];

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
