@extends('layouts.app')

@section('title', 'Tiket Digital #' . $booking->id . ' — PadelBook')

@section('styles')
<style>
    @media print {
        .navbar-padelbook, footer, .no-print { display: none !important; }
        body { background: #fff !important; }
        .ticket-card { box-shadow: none !important; border: 2px solid #ccc !important; }
    }

    .ticket-wrapper {
        max-width: 480px;
        margin: 0 auto;
    }

    /* Ticket outer card */
    .ticket-card {
        background: var(--glass-bg);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid var(--glass-border);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: var(--shadow-lg), 0 0 60px rgba(224,122,95,0.08);
        position: relative;
    }

    /* Ticket header gradient */
    .ticket-header {
        background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent) 60%, #e8a98a 100%);
        padding: 32px 32px 24px;
        position: relative;
        overflow: hidden;
    }
    .ticket-header::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
    }
    .ticket-header::after {
        content: '';
        position: absolute;
        bottom: -60px; left: -30px;
        width: 160px; height: 160px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
    }

    /* Tear/perforation line */
    .ticket-tear {
        display: flex;
        align-items: center;
        position: relative;
        margin: 0 -1px;
    }
    .ticket-tear-line {
        flex: 1;
        border-top: 2px dashed var(--border-color);
    }
    .ticket-tear-hole {
        width: 28px; height: 28px;
        border-radius: 50%;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        flex-shrink: 0;
    }
    .ticket-tear-hole.left { margin-left: -14px; }
    .ticket-tear-hole.right { margin-right: -14px; }

    /* Ticket body */
    .ticket-body {
        padding: 28px 32px 32px;
    }

    /* QR container */
    .qr-wrap {
        background: #fff;
        border-radius: 20px;
        padding: 16px;
        display: inline-block;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        border: 1px solid rgba(0,0,0,0.06);
    }

    /* Detail row */
    .detail-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-glass);
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: var(--accent-subtle);
        border: 1px solid var(--border-highlight);
        display: flex; align-items: center; justify-content: center;
        color: var(--accent); font-size: 0.85rem;
        flex-shrink: 0;
    }
    .detail-label {
        font-size: 0.7rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
    }
    .detail-value {
        font-family: var(--font-display);
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.3;
    }

    /* Slot badge */
    .slot-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 8px;
        background: linear-gradient(135deg, rgba(34,197,94,0.15), rgba(34,197,94,0.08));
        border: 1px solid rgba(34,197,94,0.3);
        color: #4ade80;
        font-family: var(--font-display);
        font-size: 0.8rem;
        font-weight: 700;
        margin: 2px;
    }

    /* Status pills */
    .status-confirmed { color: #4ade80; background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.3); }
    .status-completed { color: #60a5fa; background: rgba(96,165,250,0.12); border: 1px solid rgba(96,165,250,0.3); }
    .status-pending   { color: #fbbf24; background: rgba(251,191,36,0.12); border: 1px solid rgba(251,191,36,0.3); }
    .status-paid      { color: #4ade80; background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.3); }
    .status-pill {
        font-family: var(--font-display);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 4px 12px;
        border-radius: 999px;
        display: inline-block;
    }

    /* WA sent info box */
    .wa-info {
        background: rgba(74,222,128,0.08);
        border: 1px solid rgba(74,222,128,0.2);
        border-radius: 14px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.83rem;
        color: var(--text-secondary);
    }
    .wa-info i { color: #4ade80; font-size: 1.2rem; flex-shrink:0; }
</style>
@endsection

@section('content')
<div class="container py-4">
    <div class="ticket-wrapper">

        <!-- Back Button -->
        <div class="mb-4 no-print">
            <a href="{{ route('dashboard.bookings') }}" class="btn btn-ghost py-2 px-3" style="font-size:0.88rem;">
                <i class="fa-solid fa-arrow-left me-2" style="color:var(--accent);"></i> Kembali ke Riwayat
            </a>
        </div>

        <div class="ticket-card">
            <!-- Header -->
            <div class="ticket-header text-white">
                <div style="position:relative;z-index:1;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="width:36px;height:36px;background:rgba(255,255,255,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);">
                            <i class="fa-solid fa-table-tennis-paddle-ball" style="font-size:1rem;"></i>
                        </div>
                        <span style="font-family:var(--font-display);font-weight:800;font-size:1.1rem;letter-spacing:0.02em;">PadelBook</span>
                    </div>

                    <div style="font-family:var(--font-display);font-size:1.5rem;font-weight:800;line-height:1.2;margin-bottom:8px;">
                        Tiket Digital
                    </div>
                    <div style="font-size:0.85rem;opacity:0.85;">
                        Booking #{{ $booking->id }} &nbsp;·&nbsp; {{ $booking->date->format('d F Y') }}
                    </div>

                    <div class="mt-3">
                        @php
                            $statusClass = match($booking->status) {
                                'confirmed' => 'status-confirmed',
                                'completed' => 'status-completed',
                                default => 'status-pending'
                            };
                            $statusLabel = match($booking->status) {
                                'confirmed' => '✓ Dikonfirmasi',
                                'completed' => '✓ Selesai',
                                'pending' => '⏳ Menunggu',
                                default => strtoupper($booking->status)
                            };
                        @endphp
                        <span class="status-pill" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.3);">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tear Line -->
            <div class="ticket-tear">
                <div class="ticket-tear-hole left"></div>
                <div class="ticket-tear-line"></div>
                <div class="ticket-tear-hole right"></div>
            </div>

            <!-- Kode Tiket (Tanpa QR) -->
            <div class="ticket-body">
                <div class="text-center mb-4">
                    <div style="font-family:var(--font-display);font-size:0.78rem;font-weight:700;color:var(--text-muted);letter-spacing:0.08em;text-transform:uppercase;margin-bottom:4px;">
                        Kode Resi / Tiket
                    </div>
                    <div style="font-family:var(--font-display);font-size:1.25rem;font-weight:800;color:var(--accent);letter-spacing:0.04em;background:rgba(224,122,95,0.1);padding:12px;border-radius:12px;display:inline-block;border:1px dashed rgba(224,122,95,0.4);">
                        {{ $booking->qr_code }}
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:8px;">
                        Tunjukkan kode tiket ini di resepsionis
                    </div>
                </div>

                <!-- Detail rows -->
                <div class="mb-3">
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fa-solid fa-table-tennis-paddle-ball"></i></div>
                        <div>
                            <div class="detail-label">Lapangan</div>
                            <div class="detail-value">{{ $booking->court->name }} <span style="font-size:0.75rem;color:var(--text-muted);font-weight:500;">({{ $booking->court->type }})</span></div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fa-solid fa-calendar"></i></div>
                        <div>
                            <div class="detail-label">Tanggal Main</div>
                            <div class="detail-value">{{ $booking->date->format('d F Y') }}</div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fa-solid fa-clock"></i></div>
                        <div>
                            <div class="detail-label">Jam Sesi</div>
                            <div>
                                @foreach($booking->slots as $slot)
                                    <span class="slot-badge">{{ $slot }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fa-solid fa-user"></i></div>
                        <div>
                            <div class="detail-label">Pemesan</div>
                            <div class="detail-value">{{ $booking->user->name }}</div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fa-solid fa-tag"></i></div>
                        <div>
                            <div class="detail-label">Total Harga</div>
                            <div class="detail-value" style="color:var(--accent);">
                                Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Action buttons -->
                <div class="d-grid gap-2 no-print">
                    <button onclick="window.print()" class="btn btn-outline-sporty py-3">
                        <i class="fa-solid fa-print me-2"></i> Cetak / Simpan PDF
                    </button>
                    <a href="{{ route('dashboard.bookings') }}" class="btn btn-ghost py-3">
                        <i class="fa-solid fa-arrow-left me-2"></i> Kembali ke Riwayat
                    </a>
                </div>
            </div>
        </div>

        <!-- Footnote -->
        <div class="text-center mt-4 no-print" style="font-size:0.78rem;color:var(--text-muted);">
            <i class="fa-solid fa-shield-halved me-1" style="color:var(--accent);"></i>
            Tiket ini diverifikasi secara digital oleh PadelBook. Harap tunjukkan kode resi saat kedatangan.
        </div>

    </div>
</div>
@endsection
