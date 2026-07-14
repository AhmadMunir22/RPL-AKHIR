@extends('layouts.app')
@section('title', 'PadelBook — Platform Reservasi Lapangan Padel Premium')

@section('styles')
<style>
/* ── HERO ── */
.hero-wrap {
    min-height: 90vh;
    display: flex;
    align-items: center;
    padding: 80px 0 60px;
    position: relative;
    overflow: hidden;
}

.hero-glow-1 {
    position: absolute; top:-100px; left:-80px;
    width:600px; height:600px;
    background: radial-gradient(ellipse, rgba(224,122,95,0.14) 0%, transparent 65%);
    pointer-events: none;
}

.hero-glow-2 {
    position: absolute; bottom:-120px; right:-60px;
    width:500px; height:500px;
    background: radial-gradient(ellipse, rgba(26,45,82,0.60) 0%, transparent 65%);
    pointer-events: none;
}

/* Live Widget */
.live-widget {
    background: var(--glass-bg);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid var(--border-glass);
    border-radius: 24px;
    padding: 32px;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}

.live-widget::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--accent), var(--accent-light), transparent);
}

.live-badge {
    position: absolute; top:20px; right:20px;
    padding:5px 12px;
    background: rgba(74,222,128,0.15);
    border:1px solid rgba(74,222,128,0.40);
    border-radius: 20px;
    font-size:0.72rem;
    font-weight:700;
    font-family:var(--font-display);
    color:#22c55e;
    letter-spacing:0.06em;
    text-transform:uppercase;
    display:flex;
    align-items:center;
    gap:6px;
}

[data-theme="light"] .live-badge { color:#16a34a; background:rgba(34,197,94,0.12); border-color:rgba(34,197,94,0.35); }

/* Slot timeline */
.slot-timeline {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    max-height: 130px;
    overflow-y: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
    scrollbar-color: var(--accent-subtle) transparent;
}
.slot-timeline::-webkit-scrollbar { width: 4px; }
.slot-timeline::-webkit-scrollbar-thumb { background: var(--accent-subtle); border-radius: 4px; }

.slot-item {
    flex-shrink: 0;
    padding: 5px 13px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    font-family: var(--font-display);
    letter-spacing: 0.02em;
    border: 1.5px solid transparent;
    transition: all 0.25s ease;
}

.slot-open {
    background: rgba(74,222,128,0.15);
    color: #16a34a;
    border-color: rgba(34,197,94,0.35);
}
[data-theme="dark"] .slot-open { color:#4ade80; background:rgba(74,222,128,0.12); border-color:rgba(74,222,128,0.3); }

.slot-full {
    background: rgba(248,113,113,0.08);
    color: #9ca3af;
    border-color: rgba(248,113,113,0.15);
    text-decoration: line-through;
    opacity: 0.65;
}

/* Stats row */
.big-stat {
    font-family: var(--font-display);
    font-size: 2.6rem;
    font-weight: 800;
    line-height: 1;
    color: var(--text-primary);
}

.stat-box {
    padding: 20px;
    border-radius: 14px;
    border: 1px solid;
    text-align: center;
}
.stat-box-green {
    background: rgba(34,197,94,0.08);
    border-color: rgba(34,197,94,0.25);
}
.stat-box-accent {
    background: var(--accent-subtle);
    border-color: var(--border-highlight);
}
[data-theme="light"] .stat-box-green {
    background: rgba(34,197,94,0.06);
    border-color: rgba(34,197,94,0.25);
}
[data-theme="light"] .stat-box-accent {
    background: rgba(200,90,23,0.07);
    border-color: rgba(200,90,23,0.20);
}

/* Feature Cards */
.feature-icon-wrap {
    width: 60px; height: 60px;
    background: var(--accent-subtle);
    border: 1.5px solid var(--border-highlight);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--accent);
    margin-bottom: 16px;
    transition: var(--transition);
}

.feature-card {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-blur);
    -webkit-backdrop-filter: var(--glass-blur);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 32px 28px;
    height: 100%;
    transition: var(--transition);
}

.feature-card:hover {
    border-color: var(--border-highlight);
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg), var(--shadow-glow);
}

.feature-card:hover .feature-icon-wrap {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
}

/* Court Card */
.court-thumb {
    height: 220px;
    overflow: hidden;
    position: relative;
}

.court-thumb img, .court-thumb-placeholder {
    width: 100%; height: 100%;
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

/* Testimonial */
.review-card {
    background: rgba(17,29,58,0.60);
    backdrop-filter: blur(12px);
    border: 1px solid var(--glass-border);
    border-radius: 18px;
    padding: 24px;
}

/* CTA Section */
.cta-section {
    background: var(--glass-bg);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid var(--border-glass);
    border-radius: 24px;
    padding: 64px 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}

.cta-section::before {
    content: '';
    position: absolute; top:0; left:0; right:0; bottom:0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23e07a5f' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
</style>
@endsection

@section('content')

<!-- ═══════════════════════ HERO ═══════════════════════ -->
<section class="hero-wrap">
    <div class="hero-glow-1"></div>
    <div class="hero-glow-2"></div>

    <div class="container">
        <div class="row align-items-center g-5">

            <!-- Left: Copy -->
            <div class="col-lg-6">
                <div class="hero-badge mb-4 fade-up">
                    <span class="pulse-dot"></span>
                    LIVE SCHEDULER AKTIF
                </div>

                <h1 class="display-3 fw-bold mb-4 fade-up fade-up-delay-1" style="line-height:1.08;letter-spacing:-0.03em;">
                    Reservasi Lapangan Padel
                    <span class="text-gradient d-block">Tercepat & Real-Time</span>
                </h1>

                <p class="lead mb-5 fade-up fade-up-delay-2" style="color:var(--text-secondary);max-width:480px;line-height:1.7;">
                    Platform sport-tech modern untuk menemukan, memesan, dan bermain di lapangan padel premium pilihan Anda. Pembayaran instan, poin loyalitas, dan QR ticket digital.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-5 fade-up fade-up-delay-3">
                    <a href="{{ route('courts.index') }}" class="btn btn-sporty btn-lg px-5">
                        <i class="fa-solid fa-calendar-check"></i> Pesan Sekarang
                    </a>
                    <a href="#popular-courts" class="btn btn-outline-sporty btn-lg">
                        Lihat Lapangan <i class="fa-solid fa-arrow-down ms-1" style="font-size:0.85rem;"></i>
                    </a>
                </div>

                <!-- Trust Stats -->
                <div class="row g-3 fade-up fade-up-delay-4">
                    <div class="col-4">
                        <div style="padding:16px;background:var(--accent-subtle);border:1px solid var(--border-highlight);border-radius:14px;text-align:center;">
                            <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:var(--text-primary);">{{ $courts->count() * 3 ?: '10' }}+</div>
                            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:500;text-transform:uppercase;letter-spacing:0.05em;">Lapangan</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div style="padding:16px;background:var(--accent-subtle);border:1px solid var(--border-highlight);border-radius:14px;text-align:center;">
                            <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:var(--text-primary);">{{ $liveAvailableSlots }}</div>
                            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:500;text-transform:uppercase;letter-spacing:0.05em;">Slot Tersedia</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div style="padding:16px;background:var(--accent-subtle);border:1px solid var(--border-highlight);border-radius:14px;text-align:center;">
                            <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:var(--text-primary);">4.9★</div>
                            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:500;text-transform:uppercase;letter-spacing:0.05em;">Rating Avg</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Live Widget -->
            <div class="col-lg-6 fade-up fade-up-delay-2">
                <div class="live-widget float-anim">
                    <div class="live-badge">
                        <span class="pulse-dot"></span> LIVE
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div style="width:50px;height:50px;background:var(--accent-subtle);border:1.5px solid var(--border-highlight);border-radius:14px;display:flex;align-items:center;justify-content:center;">
                            <i class="fa-solid fa-table-tennis-paddle-ball" style="color:var(--accent);font-size:1.3rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0" style="font-family:var(--font-display);font-weight:700;color:var(--text-primary);">Ketersediaan Hari Ini</h5>
                            <small style="color:var(--text-muted);">Diperbarui otomatis setiap 30 detik</small>
                        </div>
                    </div>

                    <div class="row text-center g-3 mb-4">
                        <div class="col-6">
                            <div class="stat-box stat-box-green">
                                <div class="big-stat" id="live-slots" style="color:#16a34a;">
                                    <span x-data="{}" :style="$store.theme === 'dark' ? 'color:#4ade80' : 'color:#16a34a'">{{ $liveAvailableSlots }}</span>
                                </div>
                                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:6px;font-family:var(--font-display);font-weight:500;">Slot Tersedia</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box stat-box-accent">
                                <div class="big-stat" id="live-booked" style="color:var(--accent);">0</div>
                                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:6px;font-family:var(--font-display);font-weight:500;">Slot Terpesan</div>
                            </div>
                        </div>
                    </div>

                    <div style="padding:16px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:14px;">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left" style="color:var(--accent);font-size:0.85rem;"></i>
                                <span style="font-size:0.8rem;font-family:var(--font-display);font-weight:600;color:var(--text-primary);">Slot Hari Ini</span>
                            </div>
                            <span id="slot-updated-at" style="font-size:0.7rem;color:var(--text-muted);">Memuat...</span>
                        </div>
                        <div class="slot-timeline" id="live-slot-timeline">
                            <div class="slot-item slot-open" style="opacity:0.4;">Memuat slot...</div>
                        </div>
                        <div class="d-flex gap-3 mt-3">
                            <span style="font-size:0.72rem;color:var(--text-muted);display:flex;align-items:center;gap:5px;">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:rgba(34,197,94,0.3);border:1px solid rgba(34,197,94,0.5);"></span> Tersedia
                            </span>
                            <span style="font-size:0.72rem;color:var(--text-muted);display:flex;align-items:center;gap:5px;">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:rgba(248,113,113,0.15);border:1px solid rgba(248,113,113,0.25);"></span> Terpesan
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('courts.index') }}" class="btn btn-sporty w-100 mt-4">
                        <i class="fa-solid fa-search"></i> Cari & Pesan Lapangan
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ═══════════════════════ FEATURES ═══════════════════════ -->
<section class="py-5 my-4">
    <div class="container">
        <div class="text-center mb-5 fade-up">
            <div class="section-label mx-auto" style="justify-content:center;">Keunggulan Kami</div>
            <h2 class="display-5 fw-bold mt-2">Kenapa Pilih <span class="text-gradient">PadelBook</span>?</h2>
            <p style="color:var(--text-muted);max-width:520px;margin:12px auto 0;">Teknologi terdepan, pembayaran fleksibel, dan pengalaman booking yang mulus dari ujung jari Anda.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4 fade-up fade-up-delay-1">
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fa-solid fa-calendar-check"></i></div>
                    <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:10px;">Penjadwalan Real-Time</h5>
                    <p style="color:var(--text-muted);font-size:0.9rem;margin:0;line-height:1.7;">Lihat ketersediaan slot secara langsung dan konfirmasi booking dalam hitungan detik tanpa antri atau telepon.</p>
                </div>
            </div>
            <div class="col-md-4 fade-up fade-up-delay-2">
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fa-solid fa-wallet"></i></div>
                    <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:10px;">Multi-Metode Pembayaran</h5>
                    <p style="color:var(--text-muted);font-size:0.9rem;margin:0;line-height:1.7;">Mendukung berbagai metode pembayaran via Midtrans (Transfer Bank, QRIS, E-Wallet) maupun Transfer Manual.</p>
                </div>
            </div>
            <div class="col-md-4 fade-up fade-up-delay-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fa-solid fa-award"></i></div>
                    <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:10px;">Loyalty Reward Points</h5>
                    <p style="color:var(--text-muted);font-size:0.9rem;margin:0;line-height:1.7;">Kumpulkan poin setiap reservasi dan tukarkan dengan voucher diskon spesial untuk sesi bermain berikutnya.</p>
                </div>
            </div>
            <div class="col-md-4 fade-up fade-up-delay-1">
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fa-solid fa-qrcode"></i></div>
                    <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:10px;">QR Ticket Digital</h5>
                    <p style="color:var(--text-muted);font-size:0.9rem;margin:0;line-height:1.7;">Tiket unik berformat QR digital langsung di smartphone Anda. Check-in cepat tanpa kertas.</p>
                </div>
            </div>
            <div class="col-md-4 fade-up fade-up-delay-2">
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fa-solid fa-envelope"></i></div>
                    <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:10px;">Notifikasi Email</h5>
                    <p style="color:var(--text-muted);font-size:0.9rem;margin:0;line-height:1.7;">Konfirmasi booking, tiket QR, dan reminder jadwal otomatis dikirim ke Email Anda agar tidak pernah terlewat sesi latihan.</p>
                </div>
            </div>
            <div class="col-md-4 fade-up fade-up-delay-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fa-solid fa-star"></i></div>
                    <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:10px;">Rating & Ulasan Jujur</h5>
                    <p style="color:var(--text-muted);font-size:0.9rem;margin:0;line-height:1.7;">Baca ulasan nyata dari sesama pemain untuk memilih lapangan terbaik berdasarkan pengalaman komunitas.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════ POPULAR COURTS ═══════════════════════ -->
<section id="popular-courts" class="py-5 my-4">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-5 gap-3">
            <div>
                <div class="section-label">Lapangan Terpopuler</div>
                <h2 class="display-5 fw-bold mt-2 mb-1">Arena Pilihan <span class="text-gradient">Para Champion</span></h2>
                <p style="color:var(--text-muted);margin:0;">Lapangan dengan rating tertinggi dari member aktif PadelBook.</p>
            </div>
            <a href="{{ route('courts.index') }}" class="btn btn-outline-sporty">
                Lihat Semua <i class="fa-solid fa-arrow-right ms-1" style="font-size:0.85rem;"></i>
            </a>
        </div>

        <div class="row g-4">
            @forelse($courts as $court)
            <div class="col-md-4 fade-up">
                <div class="court-card-wrap">
                    <div class="court-thumb">
                        @if($court->primary_photo)
                            <img src="{{ $court->primary_photo }}" alt="{{ $court->name }}">
                        @else
                            <div class="court-thumb-placeholder d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,rgba(224,122,95,0.12),rgba(26,45,82,0.60));">
                                <i class="fa-solid fa-table-tennis-paddle-ball" style="font-size:3rem;color:var(--accent);opacity:0.5;"></i>
                            </div>
                        @endif
                        <div class="court-type-badge" style="position:absolute;top:12px;right:12px;padding:4px 12px;background:rgba(11,19,41,0.85);backdrop-filter:blur(8px);border:1px solid var(--border-highlight);border-radius:20px;font-family:var(--font-display);font-size:0.7rem;font-weight:700;color:var(--accent);letter-spacing:0.06em;text-transform:uppercase;">
                            {{ $court->type }}
                        </div>
                        <div style="position:absolute;top:12px;left:12px;padding:4px 12px;background:rgba(74,222,128,0.12);backdrop-filter:blur(8px);border:1px solid rgba(74,222,128,0.25);border-radius:20px;font-size:0.7rem;font-weight:700;font-family:var(--font-display);color:#4ade80;text-transform:uppercase;letter-spacing:0.05em;">
                            <i class="fa-solid fa-circle" style="font-size:0.45rem;"></i> Tersedia
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 style="font-family:var(--font-display);font-weight:700;color:var(--text-primary);margin:0;line-height:1.3;">{{ $court->name }}</h5>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0 ms-2">
                                <i class="fa-solid fa-star" style="color:#fbbf24;font-size:0.85rem;"></i>
                                <span style="font-family:var(--font-display);font-weight:700;font-size:0.9rem;color:var(--text-primary);">{{ number_format($court->rating_avg, 1) }}</span>
                            </div>
                        </div>
                        <p style="color:var(--text-muted);font-size:0.85rem;line-height:1.6;margin-bottom:16px;">{{ Str::limit($court->description, 100) }}</p>
                        <div class="divider"></div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Mulai dari</div>
                                <div style="font-family:var(--font-display);font-size:1.25rem;font-weight:800;color:var(--accent);">
                                    Rp {{ number_format($court->price_per_hour, 0, ',', '.') }}
                                    <span style="font-size:0.75rem;font-weight:500;color:var(--text-muted);">/jam</span>
                                </div>
                            </div>
                            <a href="{{ route('courts.show', $court->id) }}" class="btn btn-sporty">
                                <i class="fa-solid fa-calendar-check" style="font-size:0.85rem;"></i> Sewa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="text-center py-5" style="padding:64px 0 !important;">
                    <div style="width:80px;height:80px;background:var(--accent-subtle);border:1.5px solid var(--border-highlight);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                        <i class="fa-solid fa-table-tennis-paddle-ball" style="font-size:2rem;color:var(--accent);"></i>
                    </div>
                    <h5 style="font-family:var(--font-display);color:var(--text-secondary);">Belum ada lapangan yang terdaftar</h5>
                    <p style="color:var(--text-muted);">Lapangan akan segera tersedia. Pantau terus!</p>
                    @auth
                        @if(Auth::user()->isSuperAdmin())
                        <a href="{{ route('admin.index') }}" class="btn btn-sporty mt-2">Tambah Lapangan</a>
                        @endif
                    @endauth
                </div>
            </div>
            @endforelse
        </div>
    </div>
</section>

<!-- ═══════════════════════ HOW IT WORKS ═══════════════════════ -->
<section class="py-5 my-4">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label mx-auto" style="justify-content:center;">Cara Kerja</div>
            <h2 class="display-5 fw-bold mt-2">Booking Semudah <span class="text-gradient">3 Langkah</span></h2>
        </div>
        <div class="row g-4 align-items-center">
            <div class="col-md-4 text-center fade-up fade-up-delay-1">
                <div style="width:72px;height:72px;background:var(--accent-subtle);border:2px solid var(--border-highlight);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:var(--accent);">1</div>
                <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:8px;">Pilih Lapangan</h5>
                <p style="color:var(--text-muted);font-size:0.9rem;">Telusuri katalog lapangan padel premium kami dan pilih sesuai preferensi Anda.</p>
            </div>
            <div class="col-md-4 text-center fade-up fade-up-delay-2">
                <div style="width:72px;height:72px;background:var(--accent-subtle);border:2px solid var(--border-highlight);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:var(--accent);">2</div>
                <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:8px;">Pilih Slot & Bayar</h5>
                <p style="color:var(--text-muted);font-size:0.9rem;">Klik slot yang tersedia, lakukan pembayaran instan via berbagai metode pilihan.</p>
            </div>
            <div class="col-md-4 text-center fade-up fade-up-delay-3">
                <div style="width:72px;height:72px;background:var(--accent-subtle);border:2px solid var(--border-highlight);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:var(--accent);">3</div>
                <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:8px;">Datang & Bermain</h5>
                <p style="color:var(--text-muted);font-size:0.9rem;">Tunjukkan QR tiket digital saat tiba dan langsung nikmati sesi padel Anda!</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════ CTA ═══════════════════════ -->
<section class="py-5 my-4">
    <div class="container">
        <div class="cta-section">
            <div class="section-label mx-auto mb-3" style="justify-content:center;">Mulai Sekarang</div>
            <h2 class="display-4 fw-bold mb-3 text-adaptive">Siap Bermain Padel? 🏓</h2>
            <p style="color:var(--text-secondary);max-width:460px;margin:0 auto 36px;font-size:1.05rem;">
                Bergabunglah dengan ribuan player padel yang sudah mempercayai PadelBook untuk reservasi lapangan impian mereka.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                @guest
                <a href="{{ route('register') }}" class="btn btn-sporty btn-lg px-5">
                    <i class="fa-solid fa-user-plus"></i> Daftar Gratis Sekarang
                </a>
                <a href="{{ route('courts.index') }}" class="btn btn-outline-sporty btn-lg">
                    Lihat Lapangan
                </a>
                @endguest
                @auth
                <a href="{{ route('courts.index') }}" class="btn btn-sporty btn-lg px-5">
                    <i class="fa-solid fa-calendar-check"></i> Pesan Lapangan Sekarang
                </a>
                <a href="{{ route('dashboard.index') }}" class="btn btn-outline-sporty btn-lg">
                    Dashboard Saya
                </a>
                @endauth
            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script>
    // ── Real-time slot availability widget ──────────────────────
    const liveSlotEl    = document.getElementById('live-slots');
    const liveBookedEl  = document.getElementById('live-booked');
    const timelineEl    = document.getElementById('live-slot-timeline');
    const updatedAtEl   = document.getElementById('slot-updated-at');

    function renderSlots(data) {
        if (!data || !data.slots) return;

        // Update counters
        if (liveSlotEl)   liveSlotEl.textContent   = data.total_available ?? 0;
        if (liveBookedEl) liveBookedEl.textContent  = data.total_booked    ?? 0;

        // Render timeline pills
        if (timelineEl) {
            timelineEl.innerHTML = data.slots.map(s => {
                const isOpen = s.available > 0;
                const cls    = isOpen ? 'slot-open' : 'slot-full';
                const icon   = isOpen ? '✓' : '✗';
                const title  = isOpen
                    ? `${s.available} lapangan tersedia`
                    : 'Semua terpesan';
                return `<div class="slot-item ${cls}" title="${title}">${s.slot} ${icon}</div>`;
            }).join('');
        }

        // Update timestamp
        if (updatedAtEl) {
            const now = new Date();
            updatedAtEl.textContent = `Diperbarui ${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')}`;
        }
    }

    function fetchLiveAvailability() {
        fetch('/live-availability')
            .then(r => r.json())
            .then(data => renderSlots(data))
            .catch(() => {
                if (updatedAtEl) updatedAtEl.textContent = 'Gagal memuat';
            });
    }

    // Fetch immediately on page load, then every 30 seconds
    fetchLiveAvailability();
    setInterval(fetchLiveAvailability, 30000);

    // ── Fade-up on scroll ───────────────────────────────────────
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.style.opacity = '1';
                e.target.style.transform = 'translateY(0)';
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.fade-up').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(28px)';
        el.style.transition = 'opacity 0.6s cubic-bezier(0.4,0,0.2,1), transform 0.6s cubic-bezier(0.4,0,0.2,1)';
        observer.observe(el);
    });
</script>
@endsection
