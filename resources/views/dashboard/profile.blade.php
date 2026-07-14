@extends('layouts.app')

@section('title', 'Ubah Profil Saya - PadelBook')

@section('content')
<div class="row">
    <!-- Left column: Update profile bio info -->
    <div class="col-lg-6 mb-4">
        <div class="glass-card p-4 p-md-5 border-0">
            <h4 class="mb-4 fw-bold display-font"><i class="fa-solid fa-user-pen text-success me-2"></i> Detail Profil</h4>
            
            <form action="{{ route('dashboard.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="text-center mb-4">
                    <img src="{{ $user->avatar ?? 'https://api.dicebear.com/7.x/adventurer/svg?seed='.$user->name }}" class="rounded-circle border border-success p-1 mb-3" style="width: 110px; height: 110px; object-fit: cover;">
                    <div class="input-group input-group-sm justify-content-center">
                        <input type="file" class="form-control form-control-sporty d-none" id="avatar" name="avatar" @change="document.getElementById('avatar-label').innerText = $event.target.files[0].name">
                        <label for="avatar" class="btn btn-outline-sporty btn-sm" id="avatar-label"><i class="fa-solid fa-camera me-1"></i> Ganti Foto Profil</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label text-secondary small">Nama Lengkap</label>
                    <input type="text" class="form-control form-control-sporty" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label text-secondary small">Alamat Email</label>
                    <input type="email" class="form-control form-control-sporty" id="email" value="{{ $user->email }}" disabled>
                    <div class="form-text text-muted small">Email tidak dapat diubah setelah terdaftar.</div>
                </div>

                <div class="mb-4">
                    <label for="phone" class="form-label text-secondary small">
                        Nomor WhatsApp
                        @if(empty($user->phone))
                            <span class="badge bg-danger ms-2" style="font-size:0.7rem;"><i class="fa-solid fa-circle-exclamation me-1"></i>Wajib Diisi</span>
                        @endif
                    </label>
                    <input type="text" class="form-control form-control-sporty {{ empty($user->phone) ? 'border-danger' : '' }}" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                    @if(empty($user->phone))
                        <div class="form-text text-danger mt-2" style="font-size:0.8rem;"><i class="fa-solid fa-circle-info me-1"></i>Anda harus mengisi Nomor WhatsApp agar dapat melanjutkan pemesanan lapangan.</div>
                    @endif
                </div>

                <button type="submit" class="btn btn-sporty w-100 py-3">
                    <i class="fa-solid fa-circle-check me-2"></i> Simpan Perubahan Profil
                </button>
            </form>
        </div>
    </div>

    <!-- Right column: Update Security Password -->
    <div class="col-lg-6 mb-4">
        <div class="glass-card p-4 p-md-5 border-0">
            <h4 class="mb-4 fw-bold display-font"><i class="fa-solid fa-lock-open text-success me-2"></i> Keamanan Password</h4>
            
            <form action="{{ route('dashboard.profile.password') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="current_password" class="form-label text-secondary small">Password Saat Ini</label>
                    <input type="password" class="form-control form-control-sporty" id="current_password" name="current_password" placeholder="••••••••" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label text-secondary small">Password Baru</label>
                    <input type="password" class="form-control form-control-sporty" id="password" name="password" placeholder="Minimal 8 karakter" required autocomplete="new-password">
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label text-secondary small">Konfirmasi Password Baru</label>
                    <input type="password" class="form-control form-control-sporty" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru" required>
                </div>

                <button type="submit" class="btn btn-sporty w-100 py-3">
                    <i class="fa-solid fa-key me-2"></i> Ubah Password Saya
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
