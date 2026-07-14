<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Review;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class CourtController
 * 
 * Mengelola interaksi dengan katalog lapangan padel untuk pengguna (member/publik).
 * Menyajikan landing page dengan statistik slot dinamis, katalog dengan filter tipe
 * dan harga, detail informasi lapangan beserta kalender ketersediaan 42 cell,
 * ulasan member, serta API endpoint pendukung.
 * 
 * @package App\Http\Controllers
 */
class CourtController extends Controller
{
    /**
     * Service untuk mengambil data ketersediaan slot.
     */
    protected BookingService $bookingService;

    /**
     * CourtController constructor.
     * 
     * @param BookingService $bookingService
     */
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Landing Page Aplikasi (Sisi Publik).
     * 
     * Menampilkan 3 lapangan teratas dan menghitung statistik ketersediaan slot
     * secara langsung (live-aggregated) untuk hari ini.
     * 
     * @return View
     */
    public function landing(): View
    {
        $courts = Court::where('status', 'active')->orderBy('name', 'asc')->take(3)->get();
        
        // Kalkulasi ketersediaan slot dinamis secara real-time untuk hari ini
        $todayStr = now()->toDateString();
        $totalSlotsToday = 0;
        $bookedSlotsToday = 0;

        foreach ($courts as $court) {
            $availabilities = $this->bookingService->getAvailability($court->id, $todayStr);
            $totalSlotsToday += count($availabilities);
            foreach ($availabilities as $avail) {
                if (!$avail['is_available']) {
                    $bookedSlotsToday++;
                }
            }
        }

        $liveAvailableSlots = max(0, $totalSlotsToday - $bookedSlotsToday);

        return view('landing', compact('courts', 'liveAvailableSlots'));
    }

    /**
     * Menampilkan Katalog Lapangan dengan Filter & Pencarian.
     * 
     * @param Request $request
     * @return string|View
     */
    public function index(Request $request): string|View
    {
        $query = Court::query();

        // 1. Filter Pencarian Nama
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // 2. Filter Tipe (Indoor / Outdoor)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // 3. Filter Harga Maksimal per jam
        if ($request->filled('price_max')) {
            $query->where('price_per_hour', '<=', $request->price_max);
        }

        $courts = $query->orderBy('name')->paginate(6);

        // 4. Jika request datang dari AJAX, kembalikan markup card parsial saja (fitur search debouncing)
        if ($request->ajax()) {
            return view('courts._cards', compact('courts'))->render();
        }

        return view('courts.index', compact('courts'));
    }

    /**
     * Menampilkan Halaman Detail Lapangan & Widget Kalender Reservasi.
     * 
     * @param Request $request
     * @param int $id
     * @return View
     */
    public function show(Request $request, int $id): View
    {
        $court = Court::with(['reviews.user'])->findOrFail($id);
        $slots = $this->bookingService->getOperationalSlots();

        // Parameter navigasi kalender bulanan
        $year  = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $month = max(1, min(12, $month));

        // Bangun grid kalender 42 hari untuk bulan terpilih
        $calendar = $this->buildCalendarMonth($year, $month);

        return view('courts.show', array_merge(
            compact('court', 'slots'),
            $calendar
        ));
    }

    /**
     * Membuat Grid Kalender 6 Minggu (42 Cell) untuk Widget Booking.
     * 
     * Menghasilkan array hari-hari dalam sebulan, lengkap dengan label hari,
     * status apakah berada di luar bulan saat ini, dan status apakah tanggal telah berlalu.
     * 
     * @param int $year Tahun
     * @param int $month Bulan
     * @return array
     */
    private function buildCalendarMonth(int $year, int $month): array
    {
        $monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $first = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $today = now()->startOfDay();
        
        // Hitung hari awal grid kalender (minggu pertama dapat dimulai dari tanggal bulan sebelumnya)
        $start = $first->copy()->startOfMonth()->subDays($first->copy()->startOfMonth()->dayOfWeek);

        $calendarDays = [];
        for ($i = 0; $i < 42; $i++) {
            $d = $start->copy()->addDays($i);
            $calendarDays[] = [
                'date'     => $d->toDateString(),
                'label'    => $d->day,
                'in_month' => $d->month === $month,
                'is_today' => $d->isSameDay($today),
                'is_past'  => $d->lt($today),
            ];
        }

        $prev = $first->copy()->subMonth();
        $next = $first->copy()->addMonth();

        // Tanggal aktif di bulan ini yang belum lewat (dapat dipilih untuk reservasi)
        $calMonthDates = collect($calendarDays)
            ->filter(fn (array $day) => $day['in_month'] && ! $day['is_past'])
            ->pluck('date')
            ->values()
            ->all();

        return [
            'calYear'       => $year,
            'calMonth'      => $month,
            'calMonthLabel' => $monthNames[$month - 1] . ' ' . $year,
            'calPrevYear'   => $prev->year,
            'calPrevMonth'  => $prev->month,
            'calNextYear'   => $next->year,
            'calNextMonth'  => $next->month,
            'calendarDays'  => $calendarDays,
            'calendarWeeks' => array_chunk($calendarDays, 7),
            'calMonthDates' => $calMonthDates,
        ];
    }

    /**
     * Mengambil JSON Ketersediaan Slot Per Jam dari Lapangan Tertentu.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function availability(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        Court::findOrFail($id);
        $availabilities = $this->bookingService->getAvailability($id, $request->date);

        return response()->json([
            'date'     => $request->date,
            'court_id' => $id,
            'slots'    => $availabilities,
        ]);
    }

    /**
     * Mengambil Live Availability Teragregasi (Semua Lapangan Aktif untuk Hari Ini).
     * 
     * @return JsonResponse
     */
    public function liveAvailability(): JsonResponse
    {
        $todayStr = now()->toDateString();
        $courts = Court::where('status', 'active')->get();

        // Gabungkan seluruh ketersediaan slot lapangan aktif hari ini
        $allSlots = [];
        foreach ($courts as $court) {
            $availabilities = $this->bookingService->getAvailability($court->id, $todayStr);
            foreach ($availabilities as $avail) {
                $time = $avail['slot'];
                if (!isset($allSlots[$time])) {
                    $allSlots[$time] = ['slot' => $time, 'available' => 0, 'booked' => 0];
                }
                if ($avail['is_available']) {
                    $allSlots[$time]['available']++;
                } else {
                    $allSlots[$time]['booked']++;
                }
            }
        }

        ksort($allSlots);
        $slots = array_values($allSlots);

        $totalAvailable = collect($slots)->sum('available');
        $totalBooked    = collect($slots)->sum('booked');

        return response()->json([
            'date'            => $todayStr,
            'slots'           => $slots,
            'total_available' => $totalAvailable,
            'total_booked'    => $totalBooked,
        ]);
    }

    // --- Sanctum REST API Endpoints ---

    /**
     * Endpoint API melihat daftar lapangan aktif.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $query = Court::where('status', 'active');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $courts = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $courts
        ]);
    }

    /**
     * Endpoint API ketersediaan slot lapangan.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function apiAvailability(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d'
        ]);

        $availabilities = $this->bookingService->getAvailability($id, $request->date);

        return response()->json([
            'success'      => true,
            'court_id'     => $id,
            'date'         => $request->date,
            'availability' => $availabilities
        ]);
    }

    /**
     * Endpoint API mengambil riwayat ulasan & rating rata-rata dari lapangan.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function apiReviews(int $id): JsonResponse
    {
        $court = Court::findOrFail($id);
        $reviews = \App\Models\Review::with('user:id,name,avatar')
            ->whereHas('booking', function ($query) use ($id) {
                $query->where('court_id', $id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success'       => true,
            'rating_avg'    => number_format($court->rating_avg, 1),
            'reviews_count' => $reviews->count(),
            'reviews'       => $reviews
        ]);
    }
}

