<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PadelBook — Auth')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..800&family=Outfit:wght@300..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/css/variables.css">

    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            position: relative;
        }

        .auth-page::before {
            content:'';
            position: fixed; top:0; left:0; right:0; bottom:0;
            background: var(--bg-gradient);
            background-attachment: fixed;
            z-index: -1;
        }

        /* Decorative blobs */
        .auth-page::after {
            content:'';
            position: fixed;
            top: -100px; left: -100px;
            width: 500px; height: 500px;
            background: radial-gradient(ellipse, rgba(224,122,95,0.12) 0%, transparent 65%);
            pointer-events: none;
            z-index: 0;
        }

        .auth-card {
            background: rgba(11, 19, 41, 0.75);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
        }

        .auth-card::before {
            content:'';
            position: absolute; top:0; left:0; right:0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent), var(--accent-light), transparent);
            border-radius: 24px 24px 0 0;
        }

        @media(max-width:480px) {
            .auth-card { padding: 32px 24px; }
        }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <!-- Logo -->
        <a href="{{ route('landing') }}" class="text-decoration-none d-flex justify-content-center mb-4">
            <img src="/images/logo.jpeg" alt="PadelBook" style="height: 100px; width: auto; object-fit: cover; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        </a>

        @yield('content')

        @if($errors->any() && !$__env->hasSection('hide_global_errors'))
        <div style="margin-top:16px;padding:14px;background:rgba(248,113,113,0.10);border:1px solid rgba(248,113,113,0.25);border-radius:12px;">
            <ul class="mb-0 ps-3" style="font-size:0.85rem;color:#fca5a5;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.2/dist/cdn.min.js"></script>
@yield('scripts')
</body>
</html>
