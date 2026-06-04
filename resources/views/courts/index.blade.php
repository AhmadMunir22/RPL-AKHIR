@extends('layouts.app')
@section('title', 'Daftar Lapangan Padel — PadelBook')

@section('styles')
<style>
.filter-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 28px;
    position: sticky;
    top: 84px;
    box-shadow: var(--shadow-sm);
}

.filter-label {
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--text-muted);
    margin-bottom: 8px;
    display: block;
}

.price-range {
    -webkit-appearance: none;
    width: 100%;
    height: 5px;
    background: var(--border-color);
    border-radius: 5px;
    outline: none;
    cursor: pointer;
}
.price-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 8px var(--accent-glow);
    cursor: pointer;
    transition: transform 0.2s ease;
}
.price-range::-webkit-slider-thumb:hover { transform: scale(1.2); }

/* Court Card & Thumbnails styling for consistent sizing */
.court-thumb {
    height: 220px;
    overflow: hidden;
    position: relative;
}

.court-thumb img, .court-thumb-placeholder {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.4,0,0.2,1);
}

.court-card-wrap:hover .court-thumb img {
    transform: scale(1.06);
}

.court-card-wrap {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-blur);
    -webkit-backdrop-filter: var(--glass-blur);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    overflow: hidden;
    height: 100%;
    transition: var(--transition);
}

.court-card-wrap:hover {
    border-color: var(--border-highlight);
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg), var(--shadow-glow);
}
</style>
@endsection

@section('content')
<div class="container py-4">
    <!-- Page Header -->
    <div class="mb-5">
        <div class="section-label">Katalog Lapangan</div>
        <h1 class="display-5 fw-bold mt-2 mb-1">Semua <span class="text-gradient">Arena Padel</span></h1>
        <p style="color:var(--text-muted);">Temukan lapangan padel terbaik sesuai kebutuhan dan anggaran Anda.</p>
    </div>

    <div class="row g-4" x-data="courtFilter()">

        <!-- ── Left: Filter Panel ── -->
        <div class="col-lg-3">
            <div class="filter-card">
                <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:24px;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-sliders" style="color:var(--accent);"></i> Filter
                </h5>

                <!-- Search -->
                <div class="mb-4">
                    <span class="filter-label">Cari Nama</span>
                    <div class="position-relative">
                        <i class="fa-solid fa-search position-absolute" style="left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:0.85rem;"></i>
                        <input type="text" class="form-control form-control-sporty ps-5" id="search-input"
                               x-model="search" @input.debounce.300ms="fetchCourts()"
                               placeholder="Nama lapangan...">
                    </div>
                </div>

                <!-- Type -->
                <div class="mb-4">
                    <span class="filter-label">Tipe Lapangan</span>
                    <div class="d-flex flex-column gap-2">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;color:var(--text-secondary);font-size:0.9rem;">
                            <input type="radio" name="court-type" value="" x-model="type" @change="fetchCourts()" style="accent-color:var(--accent);"> Semua Tipe
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;color:var(--text-secondary);font-size:0.9rem;">
                            <input type="radio" name="court-type" value="Indoor" x-model="type" @change="fetchCourts()" style="accent-color:var(--accent);"> Indoor
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;color:var(--text-secondary);font-size:0.9rem;">
                            <input type="radio" name="court-type" value="Outdoor" x-model="type" @change="fetchCourts()" style="accent-color:var(--accent);"> Outdoor
                        </label>
                    </div>
                </div>

                <!-- Price -->
                <div class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="filter-label" style="margin:0;">Harga Maks.</span>
                        <span style="font-family:var(--font-display);font-size:0.85rem;font-weight:700;color:var(--accent);">
                            Rp <span x-text="formatNumber(priceMax)"></span>
                        </span>
                    </div>
                    <input type="range" class="price-range" min="50000" max="500000" step="10000"
                           x-model="priceMax" @input="fetchCourts()">
                    <div class="d-flex justify-content-between mt-1" style="font-size:0.72rem;color:var(--text-muted);">
                        <span>Rp 50K</span><span>Rp 500K</span>
                    </div>
                </div>

                <button class="btn btn-ghost w-100" @click="resetFilters()">
                    <i class="fa-solid fa-arrows-rotate me-2"></i> Reset Filter
                </button>
            </div>
        </div>

        <!-- ── Right: Courts Grid ── -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div style="font-family:var(--font-display);font-weight:600;color:var(--text-secondary);font-size:0.9rem;">
                    <span x-show="loading"><i class="fa-solid fa-spinner fa-spin me-2" style="color:var(--accent);"></i> Memuat lapangan...</span>
                    <span x-show="!loading">Menampilkan lapangan tersedia</span>
                </div>
                <div style="font-size:0.82rem;color:var(--text-muted);">
                    <i class="fa-solid fa-circle" style="color:#4ade80;font-size:0.55rem;"></i> Real-time availability
                </div>
            </div>

            <div id="courts-container">
                @include('courts._cards')
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function courtFilter() {
    return {
        search: '',
        type: '',
        priceMax: 500000,
        loading: false,

        fetchCourts() {
            this.loading = true;
            axios.get('{{ route("courts.index") }}', {
                params: { search: this.search, type: this.type, price_max: this.priceMax },
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => { document.getElementById('courts-container').innerHTML = r.data; })
            .catch(e => console.error(e))
            .finally(() => { this.loading = false; });
        },

        resetFilters() {
            this.search = ''; this.type = ''; this.priceMax = 500000;
            this.fetchCourts();
        },

        formatNumber(n) { return new Intl.NumberFormat('id-ID').format(n); }
    }
}
</script>
@endsection
