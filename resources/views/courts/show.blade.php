@extends('layouts.app')

@section('title', $court->name . ' - PadelBook')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<style>
    [x-cloak] { display: none !important; }
    .swiper-slide { border-radius: 16px; overflow: hidden; }

    /* ── Booking widget ── */
    .booking-widget { position: relative; }
    .booking-step-header {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 20px;
    }
    .booking-step-badge {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--accent), var(--accent-dark));
        color: #fff;
        font-family: var(--font-display);
        font-weight: 800;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 16px var(--accent-glow);
    }
    .booking-step-title {
        font-family: var(--font-display);
        font-weight: 800;
        font-size: 1.15rem;
        color: var(--text-primary);
        margin: 0 0 4px;
    }
    .booking-step-desc {
        font-size: 0.82rem;
        color: var(--text-muted);
        margin: 0;
    }
    .booking-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }
    .booking-legend-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-secondary);
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid var(--border-glass);
    }
    .booking-legend-pill .pip {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        box-shadow: 0 0 8px currentColor;
    }
    .booking-legend-pill.pip-avail .pip { background: #22c55e; color: #22c55e; }
    .booking-legend-pill.pip-booked .pip { background: #ef4444; color: #ef4444; }
    .booking-legend-pill.pip-past .pip { background: #6b7280; color: #6b7280; }
    .booking-legend-pill.pip-hint i { color: var(--accent); }

    .court-booking-panel {
        background: var(--bg-card);
        border: 1px solid var(--border-highlight);
        border-radius: 16px;
        padding: 16px;
        box-shadow: var(--shadow-md), inset 0 1px 0 var(--border-glass);
    }

    .court-cal-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-glass);
    }
    .court-cal-nav-btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--accent-subtle);
        border: 1px solid var(--border-highlight);
        color: var(--accent-light);
        border-radius: 10px;
        font-size: 1.1rem;
        transition: var(--transition-fast);
    }
    .court-cal-nav-btn:hover {
        background: var(--accent);
        color: #fff;
        transform: scale(1.05);
        box-shadow: 0 4px 14px var(--accent-glow);
    }
    .court-cal-title {
        font-family: var(--font-display);
        font-weight: 800;
        font-size: 1.05rem;
        color: var(--text-primary);
        letter-spacing: 0.02em;
    }

    .court-cal-dow-row,
    .court-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 6px;
    }
    .court-cal-dow {
        text-align: center;
        font-family: var(--font-display);
        font-size: 0.68rem;
        font-weight: 700;
        color: var(--accent-light);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 6px 0;
        opacity: 0.85;
    }

    .court-cal-day {
        position: relative;
        aspect-ratio: 1;
        min-height: 0;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-glass);
        border-radius: 12px;
        padding: 6px;
        cursor: pointer;
        transition: var(--transition-fast);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        color: var(--text-primary);
        width: 100%;
        overflow: hidden;
    }
    .court-cal-day::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        opacity: 0;
        background: radial-gradient(circle at 50% 0%, var(--accent-glow), transparent 70%);
        transition: opacity 0.2s;
    }
    .court-cal-day:hover:not(:disabled)::before { opacity: 1; }
    .court-cal-day:hover:not(:disabled) {
        border-color: var(--border-highlight);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }
    .court-cal-day.other-month {
        opacity: 0.25;
        pointer-events: none;
    }
    .court-cal-day.past {
        opacity: 0.4;
        cursor: not-allowed;
        filter: grayscale(0.5);
    }
    .court-cal-day.today .court-cal-day-num {
        background: var(--accent);
        color: #fff;
        width: 26px;
        height: 26px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .court-cal-day.expanded {
        border-color: var(--accent);
        background: var(--accent-subtle);
        box-shadow: 0 0 0 2px var(--accent-glow), 0 8px 20px rgba(0, 0, 0, 0.25);
        transform: scale(1.04);
        z-index: 2;
    }
    .court-cal-day-num {
        position: relative;
        z-index: 1;
        font-family: var(--font-display);
        font-weight: 700;
        font-size: 0.88rem;
        color: var(--text-primary);
        line-height: 1;
    }
    .court-cal-day-badge {
        position: relative;
        z-index: 1;
        font-size: 0.58rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 6px;
        background: rgba(34, 197, 94, 0.2);
        color: #4ade80;
        line-height: 1.2;
    }
    .court-cal-day-pips {
        position: relative;
        z-index: 1;
        display: flex;
        gap: 3px;
        margin-top: 2px;
    }
    .court-cal-day-pips .pip {
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }
    .court-cal-day-pips .pip.avail { background: #22c55e; box-shadow: 0 0 6px rgba(34, 197, 94, 0.6); }
    .court-cal-day-pips .pip.booked { background: #ef4444; box-shadow: 0 0 6px rgba(239, 68, 68, 0.5); }

    .court-cal-week { margin-bottom: 4px; }

    .court-cal-dropdown {
        position: relative;
        background: var(--bg-card);
        border: 1px solid var(--border-highlight);
        border-radius: 14px;
        padding: 0;
        margin: 8px 0 12px;
        overflow: hidden;
        animation: courtCalDrop 0.28s var(--ease-smooth);
        box-shadow: var(--shadow-md), 0 0 24px var(--accent-glow);
    }
    .court-cal-dropdown::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, var(--accent-light), var(--accent));
    }
    @keyframes courtCalDrop {
        from { opacity: 0; transform: translateY(-8px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .court-cal-dropdown-head {
        padding: 14px 16px 12px 20px;
        border-bottom: 1px solid var(--border-glass);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .court-cal-dropdown-title {
        font-family: var(--font-display);
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }
    .court-cal-dropdown-title i { color: var(--accent); }
    .court-cal-dropdown-hint {
        font-size: 0.7rem;
        color: var(--text-muted);
        white-space: nowrap;
    }
    .court-cal-dropdown-body { padding: 14px 16px 16px 20px; }
    .court-cal-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 20px;
        color: var(--text-muted);
        font-size: 0.85rem;
    }
    .court-cal-spinner {
        width: 18px;
        height: 18px;
        border: 2px solid var(--border-glass);
        border-top-color: var(--accent);
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .court-cal-slots {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(76px, 1fr));
        gap: 8px;
    }
    .court-slot {
        border-radius: 10px;
        padding: 10px 6px;
        text-align: center;
        font-family: var(--font-display);
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition-fast);
        border: 2px solid transparent;
        position: relative;
    }
    .court-slot-time { display: block; letter-spacing: 0.02em; }
    .court-slot-available {
        background: linear-gradient(145deg, #15803d 0%, #22c55e 100%);
        color: #fff;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);
    }
    .court-slot-available:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
    }
    .court-slot-available.selected {
        border-color: #fff;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.35), 0 8px 24px rgba(34, 197, 94, 0.45);
    }
    .court-slot-available.selected::after {
        content: '✓';
        position: absolute;
        top: 4px;
        right: 6px;
        font-size: 0.65rem;
        opacity: 0.9;
    }
    .court-slot-booked {
        background: linear-gradient(145deg, #991b1b 0%, #dc2626 100%);
        color: rgba(255, 255, 255, 0.9);
        cursor: not-allowed;
        opacity: 0.75;
    }
    /* Slot yang jam-nya sudah berlalu (hari ini) */
    .court-slot-past {
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.3);
        cursor: not-allowed;
        border: 2px solid rgba(255, 255, 255, 0.08);
        opacity: 0.55;
    }
    .court-slot-past .court-slot-time {
        text-decoration: line-through;
    }
    .court-slot-label {
        display: block;
        font-size: 0.58rem;
        font-weight: 600;
        opacity: 0.9;
        margin-top: 3px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .booking-selected-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        padding: 12px 14px;
        margin-bottom: 16px;
        background: var(--accent-subtle);
        border: 1px solid var(--border-highlight);
        border-radius: 12px;
    }
    .booking-selected-bar .label {
        font-size: 0.78rem;
        color: var(--text-muted);
        font-weight: 600;
    }
    .booking-slot-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 8px;
        background: var(--accent);
        color: #fff;
        font-family: var(--font-display);
        font-size: 0.75rem;
        font-weight: 700;
    }

    .booking-checkout-card {
        background: var(--bg-card);
        border: 1px solid var(--border-highlight);
        border-radius: 14px;
        padding: 18px;
        margin-bottom: 16px;
    }
    .booking-checkout-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
    }
    .booking-checkout-row + .booking-checkout-row {
        border-top: 1px solid var(--border-glass);
        padding-top: 12px;
        margin-top: 6px;
    }
    .booking-price {
        font-family: var(--font-display);
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--accent-light);
    }
</style>
@endsection

@section('content')
<div class="row" x-data="bookingWizard()" x-init="init()">
    <!-- Court Gallery & Details -->
    <div class="col-lg-6 mb-4">
        <!-- Swiper Gallery -->
        <div class="swiper mySwiper mb-4">
            <div class="swiper-wrapper">
                @if(!empty($court->display_photos))
                    @foreach($court->display_photos as $photo)
                        <div class="swiper-slide" style="height: 380px;">
                            <img src="{{ $photo }}" class="w-100 h-100" style="object-fit: cover;" alt="{{ $court->name }}">
                        </div>
                    @endforeach
                @else
                    <div class="swiper-slide" style="height: 380px;">
                        <div class="w-100 h-100 bg-success bg-opacity-25 d-flex align-items-center justify-content-center border border-secondary border-opacity-25" style="border-radius: 16px;">
                            <i class="fa-solid fa-table-tennis-paddle-ball text-success" style="font-size: 80px;"></i>
                        </div>
                    </div>
                @endif
            </div>
            <div class="swiper-pagination"></div>
        </div>

        <!-- Court Bio -->
        <div class="glass-card p-4 border-0 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold display-font mb-0">{{ $court->name }}</h2>
                <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fw-bold">{{ $court->type }}</span>
            </div>
            <div class="text-warning mb-3">
                <i class="fa-solid fa-star me-1"></i>{{ number_format($court->rating_avg, 1) }}
                <span class="text-secondary small ms-2">({{ $court->reviews->count() }} ulasan)</span>
            </div>
            <h4 class="text-success fw-bold display-font mb-3">Rp {{ number_format($court->price_per_hour, 0, ',', '.') }} <span class="text-secondary fs-6 fw-normal">/ jam</span></h4>
            <p class="text-secondary">{{ $court->description ?? 'Tidak ada deskripsi untuk lapangan ini.' }}</p>
        </div>

        <!-- Reviews Area (Real-time) -->
        <div class="glass-card p-4 border-0" x-data="courtReviews()" x-init="initReviews()">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fw-bold display-font"><i class="fa-solid fa-comments text-success me-2"></i> Ulasan Member</h4>
                <div class="spinner-border spinner-border-sm text-success" role="status" x-show="loading" x-cloak>
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            
            <div class="reviews-list" style="max-height: 300px; overflow-y: auto;">
                <template x-if="reviews.length > 0">
                    <template x-for="review in reviews" :key="review.id">
                        <div class="border-bottom border-secondary border-opacity-25 pb-3 mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-adaptive fw-bold small" x-text="review.user ? review.user.name : 'Member'"></span>
                                <span class="text-warning small">
                                    <template x-for="i in 5">
                                        <i :class="i <= review.rating ? 'fa-solid fa-star' : 'fa-regular fa-star'"></i>
                                    </template>
                                </span>
                            </div>
                            <p class="text-secondary small mb-0">"<span x-text="review.comment"></span>"</p>
                        </div>
                    </template>
                </template>

                <template x-if="!loading && reviews.length === 0">
                    <div class="text-center py-4 text-muted small">
                        <i class="fa-solid fa-message-slash fs-3 mb-2"></i>
                        <p class="mb-0">Belum ada ulasan untuk lapangan ini.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Calendar & Interactive Booking Widget -->
    <div class="col-lg-6">
        <div class="glass-card p-4 border-0 mb-4 booking-widget">
            <div class="booking-step-header">
                <span class="booking-step-badge">1</span>
                <div>
                    <h2 class="booking-step-title">Pilih Tanggal &amp; Jam</h2>
                    <p class="booking-step-desc">Klik tanggal di kalender, lalu pilih jam yang masih tersedia</p>
                </div>
            </div>

            <div class="booking-legend">
                <span class="booking-legend-pill pip-avail"><span class="pip"></span> Tersedia</span>
                <span class="booking-legend-pill pip-booked"><span class="pip"></span> Dipesan</span>
                <span class="booking-legend-pill pip-past"><span class="pip"></span> Jam Lewat</span>
                <span class="booking-legend-pill pip-hint"><i class="fa-solid fa-hand-pointer"></i> Klik hari</span>
            </div>

            <div class="court-booking-panel">
                <div class="court-cal-nav">
                    <a href="{{ route('courts.show', ['id' => $court->id, 'year' => $calPrevYear, 'month' => $calPrevMonth]) }}" class="court-cal-nav-btn text-decoration-none">&#8249;</a>
                    <span class="court-cal-title">{{ $calMonthLabel }}</span>
                    <a href="{{ route('courts.show', ['id' => $court->id, 'year' => $calNextYear, 'month' => $calNextMonth]) }}" class="court-cal-nav-btn text-decoration-none">&#8250;</a>
                </div>

                <div class="court-cal-dow-row mb-1">
                    @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $dow)
                        <div class="court-cal-dow">{{ $dow }}</div>
                    @endforeach
                </div>

                @foreach($calendarWeeks as $week)
                    @php $weekDates = collect($week)->pluck('date')->values()->all(); @endphp
                    <div class="court-cal-week">
                        <div class="court-cal-grid mb-1">
                            @foreach($week as $day)
                                <button
                                    type="button"
                                    class="court-cal-day
                                        @if(!$day['in_month']) other-month @endif
                                        @if($day['is_past']) past @endif
                                        @if($day['is_today']) today @endif"
                                    :class="{ 'expanded': expandedDate === '{{ $day['date'] }}' }"
                                    @if(!$day['in_month'] || $day['is_past']) disabled @endif
                                    @if($day['in_month'] && !$day['is_past'])
                                        @click="selectDay('{{ $day['date'] }}')"
                                    @endif
                                >
                                    <span class="court-cal-day-num">{{ $day['label'] }}</span>
                                    @if($day['in_month'] && !$day['is_past'])
                                        <span
                                            class="court-cal-day-badge"
                                            x-show="availabilityCache['{{ $day['date'] }}'] !== undefined && countAvailable('{{ $day['date'] }}') > 0"
                                            x-text="countAvailable('{{ $day['date'] }}') + ' tersedia'"
                                        ></span>
                                        <span class="court-cal-day-pips" x-show="availabilityCache['{{ $day['date'] }}'] !== undefined">
                                            <span class="pip avail" x-show="countAvailable('{{ $day['date'] }}') > 0"></span>
                                            <span class="pip booked" x-show="countBooked('{{ $day['date'] }}') > 0"></span>
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div
                    class="court-cal-dropdown"
                    x-show="expandedDate"
                    x-cloak
                >
                    <div class="court-cal-dropdown-head">
                        <p class="court-cal-dropdown-title mb-0">
                            <i class="fa-solid fa-clock me-2"></i>
                            <span x-text="formatDate(expandedDate)"></span>
                        </p>
                        <span class="court-cal-dropdown-hint">Pilih jam hijau</span>
                    </div>
                    <div class="court-cal-dropdown-body">
                        <div x-show="loadingSlots" class="court-cal-loading">
                            <span class="court-cal-spinner"></span>
                            Memuat jam...
                        </div>
                        <div x-show="!loadingSlots && slotsAvailability.length > 0" class="court-cal-slots">
                            <template x-for="item in slotsAvailability" :key="item.slot">
                                <div
                                    class="court-slot"
                                    :class="item.is_past
                                        ? 'court-slot-past'
                                        : (item.is_available
                                            ? (selectedSlots.includes(item.slot) ? 'court-slot-available selected' : 'court-slot-available')
                                            : 'court-slot-booked')"
                                    @click="!item.is_past && item.is_available && toggleSlot(item)"
                                    :title="item.is_past
                                        ? 'Jam ini sudah lewat'
                                        : (item.is_available ? ('Pilih ' + item.slot) : (item.is_blocked ? 'Pemeliharaan' : 'Sudah dipesan'))"
                                    :style="item.is_past || !item.is_available ? 'cursor:not-allowed' : 'cursor:pointer'"
                                >
                                    <span class="court-slot-time" x-text="item.slot"></span>
                                    <span class="court-slot-label"
                                        x-text="item.is_past ? 'Lewat' : (item.is_available ? 'Tersedia' : (item.is_blocked ? 'Pemeliharaan' : 'Dipesan'))"
                                    ></span>
                                </div>
                            </template>
                        </div>
                        <div x-show="!loadingSlots && expandedDate && slotsAvailability.length === 0" class="court-cal-loading">
                            <i class="fa-solid fa-calendar-xmark me-2 opacity-50"></i>
                            Tidak ada data jam untuk tanggal ini.
                        </div>
                    </div>
                </div>
            </div>

            <div class="booking-selected-bar" x-show="selectedSlots.length > 0" x-cloak>
                <span class="label">Jam dipilih:</span>
                <template x-for="slot in selectedSlots" :key="slot">
                    <span class="booking-slot-chip"><i class="fa-solid fa-clock"></i> <span x-text="slot"></span></span>
                </template>
            </div>

            <!-- Action Form checkout -->
            <div x-show="selectedSlots.length > 0" x-cloak>
                    <div class="booking-step-header mb-3">
                        <span class="booking-step-badge">2</span>
                        <div>
                            <h2 class="booking-step-title">Konfirmasi Booking</h2>
                            <p class="booking-step-desc">Periksa ringkasan sebelum lanjut ke checkout</p>
                        </div>
                    </div>

                    <div class="booking-checkout-card">
                        <div class="booking-checkout-row">
                            <span class="text-secondary small">Total Durasi</span>
                            <span class="text-adaptive fw-bold"><span x-text="selectedSlots.length"></span> jam</span>
                        </div>
                        <div class="booking-checkout-row">
                            <span class="text-secondary small">Estimasi Harga</span>
                            <span class="booking-price">Rp <span x-text="formatCurrency(selectedSlots.length * {{ $court->price_per_hour }})"></span></span>
                        </div>
                    </div>

                    @auth
                        <form action="{{ route('booking.checkout') }}" method="GET">
                            <input type="hidden" name="court_id" value="{{ $court->id }}">
                            <input type="hidden" name="date" :value="selectedDate">
                            <template x-for="slot in selectedSlots" :key="slot">
                                <input type="hidden" name="slots[]" :value="slot">
                            </template>

                            <button type="submit" class="btn btn-sporty w-100 py-3">
                                <i class="fa-solid fa-arrow-right me-2"></i>Lanjut Ke Checkout
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning text-dark border-0 p-3 mb-0" style="border-radius: 8px;">
                            <i class="fa-solid fa-lock me-2"></i> Silakan <a href="{{ route('login') }}" class="fw-bold text-dark">masuk</a> atau <a href="{{ route('register') }}" class="fw-bold text-dark">daftar</a> untuk memesan lapangan ini.
                        </div>
                    @endauth
                </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    const COURT_ID = {{ $court->id }};
    const COURT_STATUS = '{{ $court->status }}';
    const CAL_MONTH_DATES = @json($calMonthDates);

    function bookingWizard() {
        return {
            selectedDate: '',
            expandedDate: null,
            slotsAvailability: [],
            selectedSlots: [],
            availabilityCache: {},
            loadingSlots: false,

            init() {
                // Fetch semua tanggal bulan ini untuk kalender (hanya sekali)
                CAL_MONTH_DATES.forEach(date => this.fetchAvailability(date, true));

                // Real-time polling: refresh slot tanggal yang sedang dibuka setiap 5 detik
                setInterval(() => {
                    if (this.expandedDate) {
                        this.refreshActiveDate();
                    }
                }, 5000);
            },

            refreshActiveDate() {
                if (!this.expandedDate) return;
                const date = this.expandedDate;
                const params = new URLSearchParams({ date });
                fetch(`/courts/${COURT_ID}/availability?${params}`)
                    .then(r => r.ok ? r.json() : Promise.reject())
                    .then(data => {
                        const slots = data.slots || [];
                        this.availabilityCache = { ...this.availabilityCache, [date]: slots };
                        if (this.expandedDate === date) {
                            this.slotsAvailability = slots;
                            // Hapus slot yang terpilih user jika ternyata sudah dibooking orang lain
                            this.selectedSlots = this.selectedSlots.filter(s => {
                                const found = slots.find(sl => sl.slot === s);
                                return found && found.is_available;
                            });
                        }
                    })
                    .catch(() => {});
            },

            selectDay(date) {
                if (this.expandedDate === date) {
                    this.expandedDate = null;
                    return;
                }
                if (this.selectedDate !== date) {
                    this.selectedSlots = [];
                }
                this.expandedDate = date;
                this.selectedDate = date;
                this.fetchAvailability(date);
            },

            fetchAvailability(date, cacheOnly = false) {
                // Untuk tampilan kalender (cacheOnly=true), cek local cache dulu
                if (cacheOnly && this.availabilityCache[date] !== undefined) {
                    return;
                }

                if (!cacheOnly && this.expandedDate === date) {
                    this.loadingSlots = true;
                }

                const params = new URLSearchParams({ date });
                fetch(`/courts/${COURT_ID}/availability?${params}`)
                    .then(r => r.ok ? r.json() : Promise.reject())
                    .then(data => {
                        const slots = data.slots || [];
                        this.availabilityCache = { ...this.availabilityCache, [date]: slots };
                        if (!cacheOnly && this.expandedDate === date) {
                            this.slotsAvailability = slots;
                        }
                    })
                    .catch(() => {
                        this.availabilityCache = { ...this.availabilityCache, [date]: [] };
                        if (!cacheOnly && this.expandedDate === date) {
                            this.slotsAvailability = [];
                        }
                    })
                    .finally(() => {
                        if (!cacheOnly && this.expandedDate === date) {
                            this.loadingSlots = false;
                        }
                    });
            },

            countAvailable(date) {
                const slots = this.availabilityCache[date];
                if (!slots) return 0;
                // Slot past tidak dihitung sebagai tersedia
                return slots.filter(s => s.is_available && !s.is_past).length;
            },

            countBooked(date) {
                const slots = this.availabilityCache[date];
                if (!slots) return 0;
                return slots.filter(s => !s.is_available).length;
            },

            toggleSlot(item) {
                if (!item.is_available) return;
                if (this.selectedSlots.includes(item.slot)) {
                    this.selectedSlots = this.selectedSlots.filter(s => s !== item.slot);
                } else {
                    this.selectedSlots.push(item.slot);
                    this.selectedSlots.sort();
                }
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr + 'T12:00:00');
                return date.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            },

            formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID').format(amount);
            },
        };
    }

    function courtReviews() {
        return {
            reviews: @json($court->reviews->load('user:id,name,avatar')),
            loading: false,
            
            initReviews() {
                // Poll every 5 seconds for real-time reviews
                setInterval(() => {
                    this.fetchReviews();
                }, 5000);
            },
            
            fetchReviews() {
                this.loading = true;
                fetch(`/courts/${COURT_ID}/reviews`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.reviews = data.reviews;
                            // Optional: Update court rating if we wanted to bind it too
                        }
                    })
                    .finally(() => {
                        setTimeout(() => this.loading = false, 500); // Give a slight delay to show it actually polled
                    });
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swiper !== 'undefined' && document.querySelector('.mySwiper')) {
            new Swiper('.mySwiper', {
                loop: true,
                pagination: { el: '.swiper-pagination', dynamicBullets: true },
                autoplay: { delay: 4000 },
            });
        }
    });
</script>
@endsection
