@extends('layouts.auth')
@section('title', 'Masuk — PadelBook')

@section('content')
<div class="text-center mb-4">
    <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:var(--text-primary);margin-bottom:6px;">Selamat Datang Kembali 👋</h2>
    <p style="color:var(--text-muted);font-size:0.9rem;">Masuk ke akun PadelBook Anda</p>
</div>

<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label-sporty">Email</label>
        <input type="email" name="email" id="email" class="form-control form-control-sporty"
               placeholder="nama@email.com" value="{{ old('email') }}" required autofocus>
    </div>

    <div class="mb-4" x-data="{ show: false }">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label-sporty">Password</label>
            <a href="{{ route('password.forgot') }}"
               style="font-size:0.8rem;color:var(--accent);text-decoration:none;font-weight:600;"
               onmouseover="this.style.color='var(--accent-light)'"
               onmouseout="this.style.color='var(--accent)'">
                <i class="fa-solid fa-key me-1" style="font-size:0.75rem;"></i>Lupa Password?
            </a>
        </div>
        <div class="position-relative">
            <input :type="show ? 'text' : 'password'" name="password" id="password"
                   class="form-control form-control-sporty pe-5"
                   placeholder="Masukkan password" required>
            <button type="button" class="btn border-0 p-0 position-absolute"
                    style="top:50%;right:14px;transform:translateY(-50%);color:var(--text-muted);background:transparent;"
                    @click="show = !show">
                <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" style="font-size:0.9rem;"></i>
            </button>
        </div>
    </div>

    <button type="submit" id="btn-login" class="btn btn-sporty w-100 py-3 mb-3">
        <i class="fa-solid fa-right-to-bracket me-1"></i> Masuk ke Akun
    </button>
</form>

<div style="position:relative;text-align:center;margin:20px 0;">
    <div style="height:1px;background:var(--border-color);"></div>
    <span style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(11,19,41,0.75);padding:0 12px;font-size:0.78rem;color:var(--text-muted);">atau</span>
</div>

<a href="{{ route('auth.google') }}" class="btn btn-ghost w-100 py-2 d-flex align-items-center justify-content-center gap-2 mb-4">
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 01-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
        <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z" fill="#34A853"/>
        <path d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
        <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
    </svg>
    Masuk dengan Google
</a>

<p style="text-align:center;color:var(--text-muted);font-size:0.88rem;margin:0;">
    Belum punya akun?
    <a href="{{ route('register') }}" style="color:var(--accent);font-weight:600;text-decoration:none;">Daftar Gratis</a>
</p>
@endsection
