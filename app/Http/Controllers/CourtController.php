<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Web Landing Page.
     */
    public function landing()
    {
        $courts = Court::where('status', 'active')->orderBy('name', 'asc')->take(3)->get();
        
        // Calculate dynamic real-time slot statistics for today
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
     * Web Court Catalog Listing with Filters.
     */
    public function index(Request $request)
    {
        $query = Court::query();

        // Search name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter Type (Indoor / Outdoor)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter Max price
        if ($request->filled('price_max')) {
            $query->where('price_per_hour', '<=', $request->price_max);
        }

        $courts = $query->orderBy('name')->paginate(6);

        // If AJAX request, return compiled list cards for debounce search
        if ($request->ajax()) {
            return view('courts._cards', compact('courts'))->render();
        }

        return view('courts.index', compact('courts'));
    }

    /**
     * Web Court Detail Page.
     */
    public function show(Request $request, int $id)
    {
        $court = Court::with(['reviews.user'])->findOrFail($id);
        $slots = $this->bookingService->getOperationalSlots();

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $month = max(1, min(12, $month));

        $calendar = $this->buildCalendarMonth($year, $month);

        return view('courts.show', array_merge(
            compact('court', 'slots'),
            $calendar
        ));
    }

    /**
     * Build a 6-week (42 cell) calendar grid for the booking widget.
     */
    private function buildCalendarMonth(int $year, int $month): array
    {
        $monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $first = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $today = now()->startOfDay();
        $start = $first->copy()->startOfMonth()->subDays($first->copy()->startOfMonth()->dayOfWeek);

        $calendarDays = [];
        for ($i = 0; $i < 42; $i++) {
            $d = $start->copy()->addDays($i);
            $calendarDays[] = [
                'date' => $d->toDateString(),
                'label' => $d->day,
                'in_month' => $d->month === $month,
                'is_today' => $d->isSameDay($today),
                'is_past' => $d->lt($today),
            ];
        }

        $prev = $first->copy()->subMonth();
        $next = $first->copy()->addMonth();

        $calMonthDates = collect($calendarDays)
            ->filter(fn (array $day) => $day['in_month'] && ! $day['is_past'])
            ->pluck('date')
            ->values()
            ->all();

        return [
            'calYear' => $year,
            'calMonth' => $month,
            'calMonthLabel' => $monthNames[$month - 1] . ' ' . $year,
            'calPrevYear' => $prev->year,
            'calPrevMonth' => $prev->month,
            'calNextYear' => $next->year,
            'calNextMonth' => $next->month,
            'calendarDays' => $calendarDays,
            'calendarWeeks' => array_chunk($calendarDays, 7),
            'calMonthDates' => $calMonthDates,
        ];
    }

    /**
     * Public JSON: hourly slot availability for the court booking calendar.
     */
    public function availability(Request $request, int $id)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        Court::findOrFail($id);
        $availabilities = $this->bookingService->getAvailability($id, $request->date);

        return response()->json([
            'date' => $request->date,
            'court_id' => $id,
            'slots' => $availabilities,
        ]);
    }

    /**
     * Public JSON: real-time aggregated slot availability for today (used by landing widget).
     */
    public function liveAvailability()
    {
        $todayStr = now()->toDateString();
        $courts = Court::where('status', 'active')->get();

        // Aggregate all slots from all active courts for today
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

    public function apiIndex(Request $request)
    {
        $query = Court::where('status', 'active');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $courts = $query->get();

        return response()->json([
            'success' => true,
            'data' => $courts
        ]);
    }

    public function apiAvailability(Request $request, int $id)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d'
        ]);

        $availabilities = $this->bookingService->getAvailability($id, $request->date);

        return response()->json([
            'success' => true,
            'court_id' => $id,
            'date' => $request->date,
            'availability' => $availabilities
        ]);
    }

    public function apiReviews(int $id)
    {
        $court = Court::findOrFail($id);
        $reviews = \App\Models\Review::with('user:id,name,avatar')
            ->whereHas('booking', function ($query) use ($id) {
                $query->where('court_id', $id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'rating_avg' => number_format($court->rating_avg, 1),
            'reviews_count' => $reviews->count(),
            'reviews' => $reviews
        ]);
    }
}
