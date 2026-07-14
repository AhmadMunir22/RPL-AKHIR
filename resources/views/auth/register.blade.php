@extends('layouts.auth')
@section('title', 'Daftar — PadelBook')

@section('content')
<div class="text-center mb-4">
    <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:var(--text-primary);margin-bottom:6px;">Buat Akun Baru 🚀</h2>
    <p style="color:var(--text-muted);font-size:0.9rem;">Bergabung dengan komunitas padel premium</p>
</div>

<form method="POST" action="{{ route('register') }}" autocomplete="off">
    @csrf
    <div class="mb-3">
        <label class="form-label-sporty">Nama Lengkap</label>
        <input type="text" name="name" id="name" class="form-control form-control-sporty"
               placeholder="John Doe" value="{{ old('name') }}" required autofocus>
    </div>

    <div class="mb-3">
        <label class="form-label-sporty">Email</label>
        <input type="email" name="email" id="email" class="form-control form-control-sporty"
               placeholder="nama@email.com" value="{{ old('email') }}" required autocomplete="off">
    </div>

    <div class="mb-3">
        <label class="form-label-sporty">Nomor Telepon / WA</label>
        <div class="position-relative">
            <span class="position-absolute" style="left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:0.9rem;">
                <i class="fa-solid fa-phone" style="color:var(--accent);"></i>
            </span>
            <input type="tel" name="phone" id="phone" class="form-control form-control-sporty ps-5"
                   placeholder="081234567890" value="{{ old('phone') }}" required
                   inputmode="numeric" autocomplete="tel"
                   pattern="(\+62|62|0)8[0-9]{8,12}"
                   title="Format: 08xxxxxxxxxx">
        </div>
        <small class="d-block mt-1" style="color:var(--text-muted);font-size:0.78rem;opacity:0.9;">
            <i class="fa-solid fa-envelope me-1" style="color:var(--accent);"></i>Kode OTP verifikasi akan dikirim ke <strong>Email</strong> Anda.
        </small>
        @error('phone')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3" x-data="{ show: false }">
        <label class="form-label-sporty">Password</label>
        <div class="position-relative">
            <input :type="show ? 'text' : 'password'" name="password" id="password"
                   class="form-control form-control-sporty pe-5"
                   placeholder="Min. 8 karakter" required autocomplete="new-password">
            <button type="button" class="btn border-0 p-0 position-absolute"
                    style="top:50%;right:14px;transform:translateY(-50%);color:var(--text-muted);background:transparent;"
                    @click="show = !show">
                <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" style="font-size:0.9rem;"></i>
            </button>
        </div>
    </div>

    <div class="mb-4" x-data="{ show: false }">
        <label class="form-label-sporty">Konfirmasi Password</label>
        <div class="position-relative">
            <input :type="show ? 'text' : 'password'" name="password_confirmation"
                   class="form-control form-control-sporty pe-5"
                   placeholder="Ulangi password" required autocomplete="new-password">
            <button type="button" class="btn border-0 p-0 position-absolute"
                    style="top:50%;right:14px;transform:translateY(-50%);color:var(--text-muted);background:transparent;"
                    @click="show = !show">
                <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" style="font-size:0.9rem;"></i>
            </button>
        </div>
    </div>

    <button type="submit" id="btn-register" class="btn btn-sporty w-100 py-3 mb-3">
        <i class="fa-solid fa-user-plus me-1"></i> Buat Akun Gratis
    </button>
</form>

<div style="position:relative;text-align:center;margin:16px 0;">
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
    Daftar dengan Google
</a>

<p style="text-align:center;color:var(--text-muted);font-size:0.88rem;margin:0;">
    Sudah punya akun?
    <a href="{{ route('login') }}" style="color:var(--accent);font-weight:600;text-decoration:none;">Masuk Sekarang</a>
</p>
@endsection
