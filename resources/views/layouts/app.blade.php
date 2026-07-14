<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PadelBook — Reservasi Lapangan Padel Premium')</title>
    <meta name="description" content="PadelBook: Platform reservasi lapangan padel modern, real-time, dan premium. Temukan lapangan terbaik Anda.">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1329">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..800;1,9..40,300..700&family=Outfit:wght@300..900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Design System -->
    <link rel="stylesheet" href="/css/variables.css">

    @yield('styles')
    <link rel="stylesheet" href="/css/app.css">
    @yield('styles')
</head>
<body x-data="{ theme: localStorage.getItem('pb_theme') || 'dark', mobileMenu: false }"
      :data-theme="theme"
      x-init="$watch('theme', val => { localStorage.setItem('pb_theme', val); document.documentElement.setAttribute('data-theme', val); }); document.documentElement.setAttribute('data-theme', theme);">

    <!-- ═══════════════ NAVBAR ═══════════════ -->
    <nav class="navbar-padelbook">
        <div class="container d-flex align-items-center justify-content-between">

            <!-- Brand -->
            <a href="{{ route('landing') }}" class="text-decoration-none d-flex align-items-center gap-2">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,var(--accent),var(--accent-dark));border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-table-tennis-paddle-ball text-white" style="font-size:1rem;"></i>
                </div>
                <span class="navbar-brand-text">Padel<span class="text-accent">Book</span></span>
            </a>

            <!-- Center Nav (Desktop) -->
            <ul class="navbar-nav d-none d-lg-flex flex-row gap-3 mb-0 align-items-center">
                <li><a class="nav-link-custom {{ Route::is('landing') ? 'active' : '' }}" href="{{ route('landing') }}">Beranda</a></li>
                <li><a class="nav-link-custom {{ Route::is('courts.*') ? 'active' : '' }}" href="{{ route('courts.index') }}">Lapangan</a></li>
                @auth
                <li><a class="nav-link-custom {{ Route::is('dashboard.*') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">Dashboard</a></li>
                @endauth
            </ul>

            <!-- Right Side Actions -->
            <div class="d-flex align-items-center gap-2">

                <!-- Theme Toggle -->
                <button class="btn border-0 p-2 rounded-circle d-flex align-items-center justify-content-center"
                        style="width:38px;height:38px;background:var(--accent-subtle);border:1px solid var(--border-highlight) !important;"
                        @click="theme = (theme === 'dark' ? 'light' : 'dark')" title="Toggle Theme">
                    <i x-show="theme === 'dark'" class="fa-solid fa-sun" style="color:var(--accent);font-size:0.95rem;"></i>
                    <i x-show="theme === 'light'" class="fa-solid fa-moon" style="color:var(--accent);font-size:0.95rem;display:none;"></i>
                </button>

                @auth
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-ghost d-flex align-items-center gap-2 py-2 px-3"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false" id="userMenuBtn">
                            <img src="{{ Auth::user()->avatar ?? 'https://api.dicebear.com/7.x/initials/svg?seed='.urlencode(Auth::user()->name).'&backgroundColor=e07a5f&fontFamily=Outfit' }}"
                                 class="rounded-circle" style="width:26px;height:26px;object-fit:cover;">
                            <span class="d-none d-sm-inline" style="font-size:0.88rem;font-weight:600;font-family:var(--font-display);">{{ Str::limit(Auth::user()->name, 12) }}</span>
                            <i class="fa-solid fa-chevron-down" style="font-size:0.65rem;opacity:0.6;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-sporty dropdown-menu-end mt-2" style="border:none;">
                            <li>
                                <div class="px-3 py-2 mb-1" style="border-bottom:1px solid var(--border-color);">
                                    <div style="font-weight:700;font-family:var(--font-display);font-size:0.9rem;color:var(--text-primary);">{{ Auth::user()->name }}</div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);">{{ Auth::user()->email }}</div>
                                </div>
                            </li>
                            @if(Auth::user()->isSuperAdmin() || Auth::user()->isOperator())
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.index') }}">
                                    <i class="fa-solid fa-shield-halved text-accent"></i> Admin Panel
                                </a>
                            </li>
                            @endif
                            <li>
                                <a class="dropdown-item" href="{{ route('dashboard.index') }}">
                                    <i class="fa-solid fa-gauge text-accent"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('dashboard.profile') }}">
                                    <i class="fa-solid fa-user text-accent"></i> Profil Saya
                                </a>
                            </li>

                            <li><hr class="dropdown-divider my-1" style="border-color:var(--border-color);"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item" style="color:#f87171;">
                                        <i class="fa-solid fa-right-from-bracket"></i> Keluar
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-sporty py-2 px-3 d-none d-sm-inline-flex">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-sporty py-2 px-3">Daftar</a>
                @endauth

                <!-- Mobile Menu Toggle -->
                <button class="btn border-0 d-lg-none p-2" style="color:var(--text-primary);" @click="mobileMenu = !mobileMenu">
                    <i class="fa-solid fa-bars fs-5"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="container" x-show="mobileMenu" x-transition:enter="fade-up" style="display:none;">
            <div class="py-3 d-lg-none" style="border-top:1px solid var(--border-glass);margin-top:12px;">
                <a href="{{ route('landing') }}" class="d-block py-2 px-2" style="color:var(--text-secondary);font-family:var(--font-display);font-weight:500;text-decoration:none;">Beranda</a>
                <a href="{{ route('courts.index') }}" class="d-block py-2 px-2" style="color:var(--text-secondary);font-family:var(--font-display);font-weight:500;text-decoration:none;">Lapangan</a>
                @auth
                <a href="{{ route('dashboard.index') }}" class="d-block py-2 px-2" style="color:var(--text-secondary);font-family:var(--font-display);font-weight:500;text-decoration:none;">Dashboard</a>
                @endauth
                @guest
                <div class="d-flex gap-2 mt-2">
                    <a href="{{ route('login') }}" class="btn btn-outline-sporty flex-fill text-center">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-sporty flex-fill text-center">Daftar</a>
                </div>
                @endguest
            </div>
        </div>
    </nav>

    <!-- ═══════════════ FLASH MESSAGES ═══════════════ -->
    @if(session('success') || session('error') || session('warning') || $errors->any())
    <div class="container mt-3" style="max-width:900px;">
        @if(session('success'))
        <div class="alert-sporty alert-sporty-success mb-3 fade-up" role="alert">
            <i class="fa-solid fa-circle-check" style="color:#4ade80;font-size:1.1rem;"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('warning'))
        <div class="alert-sporty mb-3 fade-up" style="background-color:rgba(234,179,8,0.1);border-left:4px solid #eab308;color:var(--text-primary);" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation me-3" style="color:#eab308;font-size:1.1rem;"></i>
                <div>{{ session('warning') }}</div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
        @endif
        @if(session('error'))
        <div class="alert-sporty alert-sporty-danger mb-3 fade-up" role="alert">
            <i class="fa-solid fa-triangle-exclamation" style="color:#f87171;font-size:1.1rem;"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert-sporty alert-sporty-danger mb-3 fade-up" role="alert">
            <i class="fa-solid fa-triangle-exclamation" style="color:#f87171;font-size:1.1rem;"></i>
            <div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- ═══════════════ MAIN CONTENT ═══════════════ -->
    <main style="min-height: 75vh;">
        @yield('content')
    </main>

    <!-- ═══════════════ FOOTER ═══════════════ -->
    <footer style="background:var(--glass-bg);backdrop-filter:blur(20px);border-top:1px solid var(--border-glass);padding:48px 0 32px;margin-top:64px;">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <a href="{{ route('landing') }}" class="text-decoration-none d-flex align-items-center gap-2 mb-3">
                        <div style="width:38px;height:38px;background:linear-gradient(135deg,var(--accent),var(--accent-dark));border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fa-solid fa-table-tennis-paddle-ball text-white" style="font-size:1.05rem;"></i>
                        </div>
                        <span style="font-family:var(--font-display);font-size:1.4rem;font-weight:800;color:var(--text-primary);">Padel<span class="text-accent">Book</span></span>
                    </a>
                    <p style="color:var(--text-muted);font-size:0.88rem;line-height:1.7;max-width:280px;">Platform reservasi lapangan padel modern, real-time, dan premium untuk pengalaman bermain terbaik Anda.</p>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 style="font-family:var(--font-display);font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:16px;">Layanan</h6>
                    <ul class="list-unstyled" style="font-size:0.88rem;">
                        <li class="mb-2"><a href="{{ route('courts.index') }}" style="color:var(--text-secondary);text-decoration:none;">Cari Lapangan</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 style="font-family:var(--font-display);font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:16px;">Akun</h6>
                    <ul class="list-unstyled" style="font-size:0.88rem;">
                        <li class="mb-2"><a href="{{ route('login') }}" style="color:var(--text-secondary);text-decoration:none;">Masuk</a></li>
                        <li class="mb-2"><a href="{{ route('register') }}" style="color:var(--text-secondary);text-decoration:none;">Daftar</a></li>
                    </ul>
                </div>
            </div>
            <div class="divider"></div>
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <p style="color:var(--text-muted);font-size:0.82rem;margin:0;">&copy; {{ date('Y') }} PadelBook. All rights reserved.</p>
                <p style="color:var(--text-muted);font-size:0.82rem;margin:0;">Built for Champions 🏆</p>
            </div>
        </div>
    </footer>

    <!-- ═══════════════ JS ═══════════════ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.2/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        // Global Axios CSRF setup
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert-sporty').forEach(el => { el.style.opacity = '0'; el.style.transition = 'opacity 0.5s ease'; setTimeout(() => el.remove(), 500); });
        }, 5000);
    </script>
    @yield('scripts')
</body>
</html>
