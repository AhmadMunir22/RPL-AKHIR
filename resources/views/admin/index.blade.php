@extends('layouts.admin')
@section('title', 'Dashboard Admin — PadelBook')
@section('page-title', 'Dashboard & Statistik')

@section('content')

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
    <div>
        <div class="section-label">Admin Panel</div>
        <h1 class="fw-bold mt-2 mb-1">Statistik <span class="text-gradient">PadelBook</span></h1>
        <p style="color:var(--text-muted);margin:0;font-size:0.9rem;">Pantau performa bisnis, reservasi, dan pendapatan secara real-time.</p>
    </div>
    <a href="{{ route('admin.courts.create') }}" class="btn btn-sporty">
        <i class="fa-solid fa-plus"></i> Tambah Lapangan
    </a>
</div>

<!-- ── KPI Stats ── -->
<div class="row g-4 mb-5">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="stat-label">Total Reservasi</span>
                <div style="width:38px;height:38px;background:rgba(74,222,128,0.12);border:1px solid rgba(74,222,128,0.25);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-ticket" style="color:#4ade80;font-size:0.95rem;"></i>
                </div>
            </div>
            <div class="stat-number" id="kpi-total-bookings">{{ number_format($totalBookingsCount) }}</div>
            <div style="margin-top:8px;font-size:0.78rem;color:#4ade80;font-family:var(--font-display);font-weight:600;">
                <i class="fa-solid fa-arrow-trend-up me-1"></i> +12% bulan ini
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="stat-label">Pendapatan Hari Ini</span>
                <div style="width:38px;height:38px;background:var(--accent-subtle);border:1px solid var(--border-highlight);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-money-bill-wave" style="color:var(--accent);font-size:0.95rem;"></i>
                </div>
            </div>
            <div class="stat-number" id="kpi-today-revenue" style="font-size:1.8rem;">Rp {{ number_format($todayRevenue/1000, 0) }}K</div>
            <div style="margin-top:8px;font-size:0.78rem;color:var(--text-muted);">Pembayaran lunas hari ini</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="stat-label">Lapangan Aktif</span>
                <div style="width:38px;height:38px;background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.25);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-table-tennis-paddle-ball" style="color:#60a5fa;font-size:0.95rem;"></i>
                </div>
            </div>
            <div class="stat-number" id="kpi-active-courts">{{ $activeCourtsCount }}</div>
            <div style="margin-top:8px;font-size:0.78rem;color:var(--text-muted);">Arena tersedia untuk booking</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="stat-label">Arena Terpopuler</span>
                <div style="width:38px;height:38px;background:rgba(251,191,36,0.12);border:1px solid rgba(251,191,36,0.25);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-star" style="color:#fbbf24;font-size:0.95rem;"></i>
                </div>
            </div>
            <div id="kpi-popular-court" style="font-family:var(--font-display);font-size:1.1rem;font-weight:800;color:var(--text-primary);line-height:1.2;">{{ $popularCourtName }}</div>
            <div style="margin-top:8px;font-size:0.78rem;color:#fbbf24;font-family:var(--font-display);font-weight:600;">
                <i class="fa-solid fa-thumbs-up me-1"></i> Paling sering disewa
            </div>
        </div>
    </div>
</div>

<!-- ── Charts Row ── -->
<div class="row g-4 mb-5">
    <!-- Revenue Line Chart -->
    <div class="col-lg-8">
        <div class="glass-card p-4" style="border-radius:20px;height:100%;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 style="font-family:var(--font-display);font-weight:700;margin:0;">Pendapatan Bulanan</h5>
                    <small style="color:var(--text-muted);">Januari — Desember {{ date('Y') }}</small>
                </div>
                <div style="padding:6px 14px;background:rgba(74,222,128,0.10);border:1px solid rgba(74,222,128,0.25);border-radius:20px;font-size:0.75rem;font-weight:700;font-family:var(--font-display);color:#4ade80;">
                    <i class="fa-solid fa-arrow-trend-up me-1"></i> Revenue
                </div>
            </div>
            <div style="height:280px;"><canvas id="revenue-chart"></canvas></div>
        </div>
    </div>

    <!-- Doughnut Chart -->
    <div class="col-lg-4">
        <div class="glass-card p-4" style="border-radius:20px;height:100%;">
            <div class="mb-4">
                <h5 style="font-family:var(--font-display);font-weight:700;margin:0;">Distribusi Tipe</h5>
                <small style="color:var(--text-muted);">Indoor vs Outdoor courts</small>
            </div>
            <div style="height:220px;"><canvas id="types-chart"></canvas></div>
        </div>
    </div>
</div>

<!-- ── Courts Table ── -->
<div class="glass-card" style="border-radius:20px;overflow:hidden;">
    <div class="d-flex justify-content-between align-items-center p-4" style="border-bottom:1px solid var(--border-color);">
        <h5 style="font-family:var(--font-display);font-weight:700;margin:0;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-table-tennis-paddle-ball" style="color:var(--accent);"></i> Daftar Lapangan Padel
        </h5>
        <a href="{{ route('admin.courts.create') }}" class="btn btn-sporty py-2 px-3" style="font-size:0.82rem;">
            <i class="fa-solid fa-plus"></i> Tambah
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sporty mb-0">
            <thead>
                <tr>
                    <th>Nama Lapangan</th>
                    <th>Tipe</th>
                    <th>Harga / Jam</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="courts-table-body">
                @include('admin.partials.courts_table')
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('scripts')
<script>
function triggerPhotoUpload(id) {
    document.getElementById(`photo-input-${id}`).click();
}

function submitPhotoUpload(id) {
    const input = document.getElementById(`photo-input-${id}`);
    if (input.files.length === 0) return;
    const formData = new FormData();
    for (let i = 0; i < input.files.length; i++) formData.append('photos[]', input.files[i]);
    axios.post(`/admin/courts/${id}/upload-photos`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(() => { pollDashboardData(); })
        .catch(() => alert('Gagal upload foto.'));
}

// Revenue Chart
const ctxRev = document.getElementById('revenue-chart').getContext('2d');
const grad = ctxRev.createLinearGradient(0, 0, 0, 280);
grad.addColorStop(0, 'rgba(224,122,95,0.35)');
grad.addColorStop(1, 'rgba(224,122,95,0.02)');

window.revenueChart = new Chart(ctxRev, {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [{
            label: 'Revenue (Rp)',
            data: @json($revenueChartData),
            borderColor: 'var(--accent)',
            borderWidth: 3,
            backgroundColor: grad,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'var(--accent)',
            pointRadius: 4,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#7a94a8', font: { family: 'DM Sans' } } },
            x: { grid: { display: false }, ticks: { color: '#7a94a8', font: { family: 'DM Sans' } } }
        }
    }
});

// Types Doughnut
const ctxTypes = document.getElementById('types-chart').getContext('2d');
window.typesChart = new Chart(ctxTypes, {
    type: 'doughnut',
    data: {
        labels: @json(array_keys($typeDistribution)),
        datasets: [{
            data: @json(array_values($typeDistribution)),
            backgroundColor: ['#e07a5f', '#3b82f6', '#fbbf24', '#4ade80'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { position: 'bottom', labels: { color: '#b8cad8', font: { family: 'DM Sans', size: 12 }, padding: 16 } }
        }
    }
});

// Real-time Dashboard Polling
function pollDashboardData() {
    axios.get('/admin', { params: { ajax: 1 } })
        .then(response => {
            const data = response.data;
            
            // Update KPI cards
            document.getElementById('kpi-total-bookings').innerText = data.totalBookingsCount;
            document.getElementById('kpi-today-revenue').innerText = data.todayRevenue;
            document.getElementById('kpi-active-courts').innerText = data.activeCourtsCount;
            document.getElementById('kpi-popular-court').innerText = data.popularCourtName;
            
            // Update Table if user is not currently interacting with elements inside the table
            const activeEl = document.activeElement;
            const isInteractingInTable = activeEl && document.getElementById('courts-table-body').contains(activeEl);
            if (!isInteractingInTable) {
                document.getElementById('courts-table-body').innerHTML = data.tableHtml;
            }
            
            // Update Line Chart (smooth animation)
            if (window.revenueChart) {
                window.revenueChart.data.datasets[0].data = data.revenueChartData;
                window.revenueChart.update();
            }
            
            // Update Doughnut Chart (smooth animation)
            if (window.typesChart) {
                window.typesChart.data.labels = Object.keys(data.typeDistribution);
                window.typesChart.data.datasets[0].data = Object.values(data.typeDistribution);
                window.typesChart.update();
            }
        })
        .catch(error => {
            console.error('Error polling dashboard data:', error);
        });
}

// Poll every 5 seconds
setInterval(pollDashboardData, 5000);
</script>
@endsection
