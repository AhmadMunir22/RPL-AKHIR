<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel — PadelBook')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..800&family=Outfit:wght@300..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/css/variables.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* ── Admin Layout ── */
        html, body { height: 100%; margin: 0; padding: 0; }

        body {
            background: var(--bg-gradient);
            background-attachment: fixed;
            color: var(--text-primary);
            font-family: var(--font-body);
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .admin-sidebar {
            width: 256px;
            min-height: 100vh;
            background: rgba(6, 12, 26, 0.92);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-right: 1px solid rgba(255,255,255,0.06);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 1050;
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .admin-sidebar::-webkit-scrollbar { width: 4px; }
        .admin-sidebar::-webkit-scrollbar-thumb { background: var(--bg-tertiary); border-radius: 10px; }

        .sidebar-header {
            padding: 28px 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            margin-bottom: 12px;
        }

        .sidebar-brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            font-family: var(--font-display);
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.03em;
        }

        .admin-badge {
            display: inline-flex;
            padding: 3px 10px;
            background: var(--accent-subtle);
            border: 1px solid var(--border-highlight);
            border-radius: 20px;
            font-family: var(--font-display);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent);
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .sidebar-section-label {
            font-family: var(--font-display);
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-muted);
            padding: 12px 10px 6px;
        }

        .admin-sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            font-family: var(--font-display);
            font-size: 0.87rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
            position: relative;
            overflow: hidden;
        }

        .admin-sidebar-link i {
            width: 20px;
            text-align: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .admin-sidebar-link:hover {
            background: var(--accent-subtle);
            color: var(--accent);
        }

        .admin-sidebar-link.active {
            background: var(--accent-subtle);
            color: var(--accent);
            border-left: 3px solid var(--accent);
            padding-left: 9px;
            font-weight: 600;
        }

        .sidebar-divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin: 8px 12px;
        }

        /* ── Admin User Card at bottom ── */
        .sidebar-user {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* ── Main Content ── */
        .admin-main {
            flex: 1;
            margin-left: 256px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Top Bar ── */
        .admin-topbar {
            background: rgba(6, 12, 26, 0.70);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .admin-content {
            padding: 32px;
            flex: 1;
        }

        /* ── Mobile overlay ── */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
        }

        @media (max-width: 991px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .admin-content { padding: 20px 16px; }
            .sidebar-overlay.show { display: block; }
        }

        /* ── Live Indicator ── */
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(74,222,128,0.10);
            border: 1px solid rgba(74,222,128,0.30);
            border-radius: 20px;
            font-family: var(--font-display);
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #4ade80;
        }
        .live-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 0 0 rgba(74,222,128,0.5);
            animation: live-pulse 1.5s infinite;
            flex-shrink: 0;
        }
        @keyframes live-pulse {
            0%   { box-shadow: 0 0 0 0 rgba(74,222,128,0.6); }
            70%  { box-shadow: 0 0 0 7px rgba(74,222,128,0); }
            100% { box-shadow: 0 0 0 0 rgba(74,222,128,0); }
        }

        /* Notification item hover */
        .notif-item:hover {
            background: rgba(255,255,255,0.04) !important;
        }

        @keyframes notif-bounce {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-2px) scale(1.1); }
        }
    </style>
    @yield('styles')
</head>
<body x-data="{ sidebarOpen: false }">

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" :class="{ show: sidebarOpen }" @click="sidebarOpen = false"></div>

    <!-- ═══════ SIDEBAR ═══════ -->
    <aside class="admin-sidebar" :class="{ open: sidebarOpen }" id="adminSidebar">

        <!-- Brand / Header -->
        <div class="sidebar-header">
            <a href="{{ route('landing') }}" class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="fa-solid fa-table-tennis-paddle-ball text-white" style="font-size:1rem;"></i>
                </div>
                <span class="sidebar-brand-text">Padel<span style="color:var(--accent);">Book</span></span>
            </a>
            <span class="admin-badge">Admin Portal</span>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">

            <div class="sidebar-section-label">Utama</div>

            <a href="{{ route('admin.index') }}"
               class="admin-sidebar-link {{ Route::is('admin.index') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie"></i> Dashboard & Statistik
            </a>

            <div class="sidebar-section-label">Manajemen</div>

            <a href="{{ route('admin.courts.index') }}"
               class="admin-sidebar-link {{ Route::is('admin.courts.*') ? 'active' : '' }}">
                <i class="fa-solid fa-table-tennis-paddle-ball"></i> Kelola Lapangan
            </a>

            <a href="{{ route('admin.bookings') }}"
               class="admin-sidebar-link {{ Route::is('admin.bookings') ? 'active' : '' }}">
                <i class="fa-solid fa-ticket"></i> Kelola Reservasi
            </a>

            <a href="{{ route('admin.blocked-slots') }}"
               class="admin-sidebar-link {{ Route::is('admin.blocked-slots') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-minus"></i> Blokir Slot
            </a>

            <a href="{{ route('admin.vouchers') }}"
               class="admin-sidebar-link {{ Route::is('admin.vouchers') ? 'active' : '' }}">
                <i class="fa-solid fa-tags"></i> Voucher & Promo
            </a>

            <div class="sidebar-section-label">Laporan</div>

            <a href="{{ route('admin.reports') }}"
               class="admin-sidebar-link {{ Route::is('admin.reports') ? 'active' : '' }}">
                <i class="fa-solid fa-file-chart-column"></i> Laporan Keuangan
            </a>

            <a href="{{ route('admin.logs') }}"
               class="admin-sidebar-link {{ Route::is('admin.logs') ? 'active' : '' }}">
                <i class="fa-solid fa-list-check"></i> Log Aktivitas
            </a>

            <div class="sidebar-divider"></div>

            <a href="{{ route('landing') }}" class="admin-sidebar-link">
                <i class="fa-solid fa-globe"></i> Lihat Situs Publik
            </a>

        </nav>

        <!-- Sidebar User Footer -->
        <div class="sidebar-user">
            <img src="{{ Auth::user()->avatar ?? 'https://api.dicebear.com/7.x/initials/svg?seed='.urlencode(Auth::user()->name).'&backgroundColor=e07a5f' }}"
                 style="width:36px;height:36px;border-radius:10px;object-fit:cover;border:2px solid var(--accent);flex-shrink:0;">
            <div style="flex:1;min-width:0;">
                <div style="font-family:var(--font-display);font-weight:700;font-size:0.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text-primary);">{{ Auth::user()->name }}</div>
                <div style="font-size:0.7rem;color:var(--text-muted);">{{ ucfirst(Auth::user()->role ?? 'Admin') }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn border-0 p-1" style="color:var(--text-muted);" title="Keluar">
                    <i class="fa-solid fa-right-from-bracket" style="font-size:0.9rem;"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- ═══════ MAIN AREA ═══════ -->
    <div class="admin-main">

        <!-- Top Bar -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <!-- Mobile toggle -->
                <button class="btn border-0 d-lg-none p-1" @click="sidebarOpen = !sidebarOpen" style="color:var(--text-primary);">
                    <i class="fa-solid fa-bars fs-5"></i>
                </button>
                <!-- Breadcrumb -->
                <div style="font-family:var(--font-display);font-size:0.88rem;color:var(--text-muted);">
                    Admin Panel
                    <i class="fa-solid fa-chevron-right mx-2" style="font-size:0.65rem;"></i>
                    <span style="color:var(--text-primary);font-weight:600;">@yield('page-title', 'Dashboard')</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Live Real-time Badge -->
                <div class="live-badge d-none d-sm-flex">
                    <span class="live-dot"></span>
                    Live
                </div>
                <!-- Time -->
                <div style="font-family:var(--font-display);font-size:0.82rem;color:var(--text-muted);" id="admin-clock"></div>
                <!-- Notifications Dropdown -->
                <div style="position:relative;" class="dropdown">
                    <button class="btn border-0 p-2 position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                        style="color:var(--text-secondary);background:var(--accent-subtle);border-radius:10px;border:1px solid var(--border-highlight) !important;transition:all 0.2s;">
                        <i class="fa-solid fa-bell" style="font-size:0.95rem;"></i>
                        @php
                            $newBookingsCount = \App\Models\Booking::where('payment_status', 'paid')->whereDate('updated_at', today())->count();
                        @endphp
                        @if($newBookingsCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                                style="background: #ef4444; font-size:0.58rem; padding: 0.3em 0.55em; min-width: 18px; animation: notif-bounce 1s ease-in-out;">
                                {{ $newBookingsCount > 9 ? '9+' : $newBookingsCount }}
                            </span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-0 overflow-hidden"
                        style="width: 360px; background: #0f172a; border: 1px solid rgba(255,255,255,0.10) !important; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">

                        {{-- Header --}}
                        <li class="d-flex align-items-center justify-content-between px-4 py-3"
                            style="border-bottom: 1px solid rgba(255,255,255,0.07); background: rgba(255,255,255,0.03);">
                            <div>
                                <h6 class="mb-0 fw-bold" style="color:#fff; font-family:var(--font-display); font-size:0.9rem;">
                                    <i class="fa-solid fa-bell me-2" style="color:var(--accent);"></i>Notifikasi
                                </h6>
                                <div style="font-size:0.68rem; color:rgba(255,255,255,0.45); margin-top:2px;">Pembayaran masuk hari ini</div>
                            </div>
                            @if($newBookingsCount > 0)
                                <span style="background:rgba(74,222,128,0.15); color:#4ade80; font-size:0.65rem; font-weight:700; padding:3px 10px; border-radius:20px; border:1px solid rgba(74,222,128,0.25);">
                                    {{ $newBookingsCount }} baru
                                </span>
                            @endif
                        </li>

                        {{-- Notification list --}}
                        @php
                            $recentBookings = \App\Models\Booking::with(['user', 'court'])->where('payment_status', 'paid')->latest('updated_at')->take(6)->get();
                        @endphp
                        <div style="max-height: 340px; overflow-y: auto;">
                            @forelse($recentBookings as $notif)
                                <li>
                                    <a class="d-flex align-items-start gap-3 px-4 py-3 notif-item text-decoration-none"
                                        href="{{ route('admin.bookings') }}"
                                        style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.15s;">
                                        {{-- Icon --}}
                                        <div class="flex-shrink-0 mt-1">
                                            <div style="width:38px; height:38px; background: linear-gradient(135deg, rgba(74,222,128,0.25), rgba(74,222,128,0.08)); border:1px solid rgba(74,222,128,0.25); border-radius:12px; display:flex; align-items:center; justify-content:center;">
                                                <i class="fa-solid fa-circle-check" style="color:#4ade80; font-size:1rem;"></i>
                                            </div>
                                        </div>
                                        {{-- Content --}}
                                        <div style="min-width:0; flex:1;">
                                            <div style="color:#fff; font-weight:700; font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                {{ $notif->user->name ?? 'User' }}
                                            </div>
                                            <div style="color:rgba(255,255,255,0.65); font-size:0.78rem; margin-top:2px;">
                                                <i class="fa-solid fa-table-tennis-paddle-ball me-1" style="color:var(--accent); font-size:0.7rem;"></i>{{ $notif->court->name ?? '-' }}
                                                &nbsp;&bull;&nbsp;
                                                <span style="color:#f0fdf4;">{{ $notif->date?->format('d M Y') ?? '-' }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <span style="color:#4ade80; font-weight:700; font-size:0.8rem;">Rp {{ number_format($notif->total_price, 0, ',', '.') }}</span>
                                                <span style="color:rgba(255,255,255,0.35); font-size:0.65rem;">
                                                    <i class="fa-regular fa-clock me-1"></i>{{ $notif->updated_at->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @empty
                                <li class="py-5 text-center">
                                    <i class="fa-regular fa-bell-slash mb-3" style="color:rgba(255,255,255,0.2); font-size:2rem;"></i>
                                    <div style="color:rgba(255,255,255,0.4); font-size:0.82rem;">Belum ada pembayaran hari ini</div>
                                </li>
                            @endforelse
                        </div>

                        {{-- Footer --}}
                        <li style="border-top: 1px solid rgba(255,255,255,0.07); background: rgba(255,255,255,0.02);">
                            <a href="{{ route('admin.bookings') }}" class="d-flex align-items-center justify-content-center gap-2 py-3 text-decoration-none"
                                style="color:var(--accent); font-size:0.8rem; font-weight:700; font-family:var(--font-display); transition:opacity 0.2s;">
                                Lihat Semua Booking <i class="fa-solid fa-arrow-right" style="font-size:0.7rem;"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash Alerts -->
        @if(session('success') || session('error') || $errors->any())
        <div style="padding:16px 32px 0;">
            @if(session('success'))
            <div class="alert-sporty alert-sporty-success mb-3">
                <i class="fa-solid fa-circle-check" style="color:#4ade80;font-size:1.1rem;"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert-sporty alert-sporty-danger mb-3">
                <i class="fa-solid fa-triangle-exclamation" style="color:#f87171;"></i>
                <div>{{ session('error') }}</div>
            </div>
            @endif
            @if($errors->any())
            <div class="alert-sporty alert-sporty-danger mb-3">
                <i class="fa-solid fa-triangle-exclamation" style="color:#f87171;"></i>
                <div><ul class="mb-0 ps-3 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            </div>
            @endif
        </div>
        @endif

        <!-- Main Content -->
        <main class="admin-content">
            @yield('content')
        </main>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.2/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        // Admin clock
        const clockEl = document.getElementById('admin-clock');
        function updateClock() {
            const now = new Date();
            clockEl.textContent = now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' }) + ' WIB';
        }
        updateClock(); setInterval(updateClock, 1000);

        // Axios CSRF
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    </script>
    @yield('scripts')
</body>
</html>
