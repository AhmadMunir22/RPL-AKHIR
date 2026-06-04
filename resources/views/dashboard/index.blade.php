@extends('layouts.app')
@section('title', 'Dashboard — PadelBook')

@section('content')
<div class="container py-4">

    <!-- ── Welcome Header ── -->
    <div class="mb-5">
        <div class="section-label">Member Area</div>
        <h1 class="fw-bold mt-2 mb-1">
            Halo, <span class="text-gradient">{{ Str::words($user->name, 2, '') }}</span> 👋
        </h1>
        <p style="color:var(--text-muted);">Selamat datang di dashboard PadelBook Anda. Kelola reservasi, saldo, dan poin di sini.</p>
    </div>

    <!-- ── Stat Cards ── -->
    <div class="row g-4 mb-5">
        <!-- Points -->
        <div class="col-md-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span style="font-family:var(--font-display);font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);">Poin Loyalitas</span>
                    <div style="width:40px;height:40px;background:var(--accent-subtle);border:1px solid var(--border-highlight);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-award" style="color:var(--accent);"></i>
                    </div>
                </div>
                <div class="stat-number">{{ number_format($user->points) }}</div>
                <div class="stat-label">Reward Points</div>
                <a href="{{ route('dashboard.loyalty') }}" class="btn btn-ghost mt-3 py-2 px-4" style="font-size:0.82rem;">
                    <i class="fa-solid fa-gift me-1"></i> Tukar Reward
                </a>
            </div>
        </div>

        <!-- Total Bookings -->
        <div class="col-md-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span style="font-family:var(--font-display);font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);">Total Booking</span>
                    <div style="width:40px;height:40px;background:var(--accent-subtle);border:1px solid var(--border-highlight);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-calendar-check" style="color:var(--accent);"></i>
                    </div>
                </div>
                <div class="stat-number">{{ $user->bookings->count() }}</div>
                <div class="stat-label">Reservasi Seluruhnya</div>
                <a href="{{ route('dashboard.bookings') }}" class="btn btn-ghost mt-3 py-2 px-4" style="font-size:0.82rem;">
                    <i class="fa-solid fa-eye me-1"></i> Lihat Riwayat
                </a>
            </div>
        </div>
    </div>

    <!-- ── Recent Bookings + Quick Links ── -->
    <div class="row g-4">

        <!-- Recent Bookings Table -->
        <div class="col-lg-8">
            <div class="glass-card" style="border-radius:20px;overflow:hidden;">
                <div class="d-flex justify-content-between align-items-center p-4" style="border-bottom:1px solid var(--border-color);">
                    <h5 style="font-family:var(--font-display);font-weight:700;margin:0;display:flex;align-items:center;gap:8px;">
                        <i class="fa-solid fa-clock-rotate-left" style="color:var(--accent);"></i> Booking Terbaru
                    </h5>
                    <a href="{{ route('dashboard.bookings') }}" style="font-family:var(--font-display);font-size:0.82rem;font-weight:600;color:var(--accent);text-decoration:none;">
                        Lihat Semua <i class="fa-solid fa-arrow-right ms-1" style="font-size:0.7rem;"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sporty mb-0">
                        <thead>
                            <tr>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Bayar</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBookings as $booking)
                            <tr>
                                <td style="font-weight:600;color:var(--text-primary);">{{ $booking->court->name }}</td>
                                <td style="color:var(--text-secondary);font-size:0.88rem;">
                                    {{ $booking->date->format('d M Y') }}
                                </td>
                                <td>
                                    @php
                                    $statusColors = ['confirmed'=>'badge-active','pending'=>'badge-pending','completed'=>'badge-paid','cancelled'=>'badge-inactive'];
                                    @endphp
                                    <span class="badge-sporty {{ $statusColors[$booking->status] ?? 'badge-pending' }}">
                                        {{ strtoupper($booking->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-sporty {{ $booking->payment_status === 'paid' ? 'badge-paid' : 'badge-pending' }}">
                                        {{ strtoupper($booking->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('dashboard.bookings.ticket', $booking->id) }}"
                                       class="btn btn-ghost py-1 px-3" style="font-size:0.78rem;">
                                        <i class="fa-solid fa-qrcode"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div style="color:var(--text-muted);font-size:0.9rem;">
                                        <i class="fa-solid fa-calendar-xmark mb-2" style="font-size:1.5rem;display:block;color:var(--border-color);"></i>
                                        Belum ada riwayat booking.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Access Panel -->
        <div class="col-lg-4">
            <div class="glass-card p-4" style="border-radius:20px;">
                <h5 style="font-family:var(--font-display);font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-bolt" style="color:var(--accent);"></i> Aksi Cepat
                </h5>
                <div class="d-grid gap-2">
                    <a href="{{ route('courts.index') }}" class="btn btn-sporty py-3 text-start">
                        <i class="fa-solid fa-search me-2"></i> Cari & Pesan Lapangan
                    </a>
                    <a href="{{ route('dashboard.profile') }}" class="btn btn-ghost py-3 text-start">
                        <i class="fa-solid fa-user-pen me-2"></i> Edit Profil Saya
                    </a>
                    <a href="{{ route('dashboard.loyalty') }}" class="btn btn-ghost py-3 text-start">
                        <i class="fa-solid fa-gift me-2"></i> Tukar Poin Loyalitas
                    </a>
                </div>
            </div>

            <!-- User Profile Mini Card -->
            <div class="glass-card p-4 mt-4" style="border-radius:20px;text-align:center;">
                <img src="{{ $user->avatar ?? 'https://api.dicebear.com/7.x/initials/svg?seed='.urlencode($user->name).'&backgroundColor=e07a5f&fontFamily=Outfit&fontSize=38' }}"
                     class="rounded-circle mb-3" style="width:72px;height:72px;object-fit:cover;border:3px solid var(--accent);">
                <h6 style="font-family:var(--font-display);font-weight:700;margin:0;">{{ $user->name }}</h6>
                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;margin-bottom:12px;">{{ $user->email }}</div>
                <span class="badge-sporty badge-terracotta">
                    {{ ucfirst($user->role ?? 'Member') }}
                </span>
            </div>
        </div>

    </div>
</div>
@endsection
