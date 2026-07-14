@extends('layouts.auth')
@section('title', 'Lupa Password — PadelBook')
@section('hide_global_errors', true)

@section('content')

{{-- Inline styles for Premium UI --}}
<style>
/* Premium Step Tracker */
.steps-wrapper {
    position: relative;
    display: flex;
    justify-content: space-between;
    margin-bottom: 32px;
}
.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
    z-index: 2;
}
.step-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 700;
    font-family: var(--font-display);
    transition: all 0.3s var(--ease-smooth);
    background: var(--bg-secondary);
    color: var(--text-muted);
    border: 2px solid rgba(255, 255, 255, 0.1);
}
.step-item.active .step-circle {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
    box-shadow: 0 0 15px var(--accent-glow);
}
.step-item.completed .step-circle {
    background: rgba(74, 222, 128, 0.15);
    color: #4ade80;
    border-color: rgba(74, 222, 128, 0.4);
}
.step-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-top: 8px;
    font-family: var(--font-display);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    transition: color 0.3s ease;
    text-align: center;
}
.step-item.active .step-label {
    color: var(--accent);
    font-weight: 700;
}
.step-item.completed .step-label {
    color: #4ade80;
}

/* Premium Form Group */
.premium-input-group {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.02);
    border: 1.5px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 6px;
    transition: all 0.25s var(--ease-smooth);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15);
}
.premium-input-group:focus-within {
    border-color: var(--accent);
    box-shadow: 0 0 0 4px var(--accent-glow), inset 0 2px 4px rgba(0, 0, 0, 0.05);
    background: rgba(255, 255, 255, 0.04);
}
.premium-input-group.is-invalid {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15), inset 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}
.premium-input-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: rgba(74, 222, 128, 0.08);
    color: #4ade80;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.25s var(--ease-smooth);
    margin-right: 12px;
    flex-shrink: 0;
}
.premium-input-group:focus-within .premium-input-icon {
    background: var(--accent-subtle);
    color: var(--accent);
    transform: scale(1.05);
}
.premium-input-field {
    flex: 1;
    min-width: 0;
    background: transparent !important;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    padding: 10px 12px 10px 0 !important;
    color: var(--text-primary) !important;
    font-size: 1rem;
    font-family: var(--font-display);
    font-weight: 600;
    caret-color: var(--accent);
}
.premium-input-field::placeholder {
    color: rgba(255, 255, 255, 0.25);
    font-weight: 400;
}

/* Premium Button Styling */
.btn-premium {
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    border: none;
    border-radius: 14px;
    padding: 14px;
    font-family: var(--font-display);
    font-weight: 700;
    font-size: 0.95rem;
    letter-spacing: 0.02em;
    color: #fff !important;
    box-shadow: 0 4px 15px var(--accent-glow);
    transition: all 0.25s var(--ease-smooth);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}
.btn-premium:hover {
    background: linear-gradient(135deg, var(--accent-light), var(--accent));
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(224, 122, 95, 0.45);
}
.btn-premium:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px var(--accent-glow);
}
.btn-premium i {
    transition: transform 0.25s var(--ease-smooth);
}
.btn-premium:hover i {
    transform: translateX(4px) translateY(-2px);
}

@keyframes pulse-email {
    0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); transform: scale(1); }
    50% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.2); transform: scale(1.05); }
}
.email-icon-glow {
    animation: pulse-email 3s infinite ease-in-out;
}

/* Suppress redundant global layout errors */
.auth-card > div:has(ul) {
    display: none !important;
}
</style>

{{-- Icon + Heading --}}
<div class="text-center mb-4">
    <div class="email-icon-glow" style="display:inline-flex;align-items:center;justify-content:center;
        width:68px;height:68px;border-radius:50%;margin-bottom:16px;
        background:linear-gradient(135deg,rgba(59,130,246,0.18),rgba(59,130,246,0.05));
        border:2px solid rgba(59,130,246,0.4);">
        <i class="fa-solid fa-envelope" style="font-size:1.8rem;color:#3b82f6;"></i>
    </div>
    <h2 style="font-family:var(--font-display);font-size:1.55rem;font-weight:800;color:var(--text-primary);margin-bottom:8px;letter-spacing:-0.01em;">
        Lupa Password?
    </h2>
    <p style="color:var(--text-secondary);font-size:0.88rem;line-height:1.55;margin:0;max-width:320px;margin-left:auto;margin-right:auto;">
        Masukkan alamat Email terdaftar. Kode verifikasi (OTP) akan dikirim ke <strong style="color:#3b82f6;font-weight:700;">email tersebut</strong>.
    </p>
</div>

{{-- Step indicator --}}
<div class="steps-wrapper">
    <!-- Background progress line -->
    <div style="position: absolute; top: 16px; left: 15%; right: 15%; height: 2px; background: rgba(255,255,255,0.08); z-index: 1;">
        <div style="width: 0%; height: 100%; background: linear-gradient(90deg, #4ade80, var(--accent)); transition: width 0.3s ease;"></div>
    </div>
    
    <!-- Step 1 active -->
    <div class="step-item active">
        <div class="step-circle">1</div>
        <span class="step-label">Email</span>
    </div>
    
    <!-- Step 2 inactive -->
    <div class="step-item">
        <div class="step-circle">2</div>
        <span class="step-label">Verifikasi</span>
    </div>
    
    <!-- Step 3 inactive -->
    <div class="step-item">
        <div class="step-circle">3</div>
        <span class="step-label">Password</span>
    </div>
</div>

<form method="POST" action="{{ route('password.forgot') }}">
    @csrf
    <div class="mb-4">
        <label class="form-label-sporty" style="margin-bottom: 8px;">
            Alamat Email
        </label>

        <div class="premium-input-group @error('email') is-invalid @enderror">
            <div class="premium-input-icon" style="background:rgba(59,130,246,0.08);color:#3b82f6;">
                <i class="fa-solid fa-envelope"></i>
            </div>
            <input type="email" name="email" id="email"
                   class="premium-input-field"
                   placeholder="email@example.com"
                   value="{{ old('email') }}"
                   autocomplete="email"
                   required autofocus>
        </div>

        @error('email')
        <div style="margin-top:10px;display:flex;align-items:center;gap:8px;padding:10px 14px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);border-radius:12px;">
            <i class="fa-solid fa-circle-exclamation" style="color:#ef4444;font-size:0.9rem;flex-shrink:0;"></i>
            <span style="font-size:0.8rem;color:#fca5a5;font-weight:500;">{{ $message }}</span>
        </div>
        @enderror

        <div style="margin-top:10px;display:flex;align-items:flex-start;gap:8px;">
            <i class="fa-solid fa-circle-info" style="color:var(--accent);font-size:0.82rem;margin-top:3px;flex-shrink:0;"></i>
            <span style="font-size:0.78rem;color:var(--text-muted);line-height:1.45;">
                Gunakan email yang Anda daftarkan saat membuat akun.
            </span>
        </div>
    </div>

    <button type="submit" class="btn-premium mb-4">
        <span>Kirim Kode OTP</span>
        <i class="fa-solid fa-paper-plane" style="font-size:0.85rem;"></i>
    </button>
</form>

<div style="text-align:center;">
    <a href="{{ route('login') }}" style="font-size:0.85rem;color:var(--text-muted);text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:var(--transition-fast);"
       onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-muted)'">
        <i class="fa-solid fa-arrow-left" style="font-size:0.75rem;"></i> Kembali ke Login
    </a>
</div>

@endsection
