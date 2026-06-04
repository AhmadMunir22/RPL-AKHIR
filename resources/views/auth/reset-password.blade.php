@extends('layouts.auth')
@section('title', 'Reset Password Baru — PadelBook')

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
    position: relative;
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
.premium-input-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
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
    padding: 10px 48px 10px 0 !important; /* right padding for toggle eye */
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

@keyframes float-key {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-5px) rotate(5deg); }
}
.key-icon-glow {
    animation: float-key 4s infinite ease-in-out;
    box-shadow: 0 0 25px var(--accent-glow);
}

/* Suppress redundant global layout errors */
.auth-card > div:has(ul) {
    display: none !important;
}
</style>

{{-- Icon + Heading --}}
<div class="text-center mb-4">
    <div class="key-icon-glow" style="
        display:inline-flex;align-items:center;justify-content:center;
        width:68px;height:68px;border-radius:50%;margin-bottom:16px;
        background:linear-gradient(135deg,rgba(224,122,95,0.18),rgba(224,122,95,0.06));
        border:2px solid rgba(224,122,95,0.35);
    ">
        <i class="fa-solid fa-key" style="font-size:1.8rem;color:var(--accent);"></i>
    </div>
    <h2 style="font-family:var(--font-display);font-size:1.55rem;font-weight:800;color:var(--text-primary);margin-bottom:8px;letter-spacing:-0.01em;">
        Buat Password Baru
    </h2>
    <p style="color:var(--text-secondary);font-size:0.88rem;line-height:1.55;margin:0;max-width:320px;margin-left:auto;margin-right:auto;">
        Identitas Anda telah terverifikasi. Silakan buat password baru yang kuat untuk akun Anda.
    </p>
</div>

{{-- Step indicator --}}
<div class="steps-wrapper">
    <!-- Background progress line -->
    <div style="position: absolute; top: 16px; left: 15%; right: 15%; height: 2px; background: rgba(255,255,255,0.08); z-index: 1;">
        <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #4ade80, var(--accent)); transition: width 0.3s ease;"></div>
    </div>
    
    <!-- Step 1 completed -->
    <div class="step-item completed">
        <div class="step-circle">
            <i class="fa-solid fa-check" style="font-size:0.8rem;"></i>
        </div>
        <span class="step-label">No. WA</span>
    </div>
    
    <!-- Step 2 completed -->
    <div class="step-item completed">
        <div class="step-circle">
            <i class="fa-solid fa-check" style="font-size:0.8rem;"></i>
        </div>
        <span class="step-label">Verifikasi</span>
    </div>
    
    <!-- Step 3 active -->
    <div class="step-item active">
        <div class="step-circle">3</div>
        <span class="step-label">Password</span>
    </div>
</div>

<form method="POST" action="{{ route('password.reset.form') }}" x-data="{ showPass: false, showConf: false, strength: 0, pass: '' }"
      @submit.prevent="$el.submit()">
      @csrf

    {{-- Password baru --}}
    <div class="mb-3">
        <label class="form-label-sporty" style="margin-bottom: 8px;">Password Baru</label>
        <div class="premium-input-group">
            <div class="premium-input-icon">
                <i class="fa-solid fa-lock"></i>
            </div>
            <input :type="showPass ? 'text' : 'password'"
                   name="password" id="password"
                   class="premium-input-field"
                   placeholder="Minimal 8 karakter"
                   x-model="pass"
                   @input="strength = calcStrength(pass)"
                   required minlength="8">
            <button type="button" class="btn border-0 p-0 position-absolute"
                    style="top:50%;right:16px;transform:translateY(-50%);color:var(--text-muted);background:transparent;z-index:3;width:24px;height:24px;display:flex;align-items:center;justify-content:center;"
                    @click="showPass = !showPass">
                <i :class="showPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" style="font-size:0.9rem;"></i>
            </button>
        </div>

        {{-- Password strength bar --}}
        <div style="margin-top:10px;height:4px;border-radius:4px;background:rgba(255,255,255,0.06);overflow:hidden;">
            <div :style="`width:${strength}%;transition:all 0.3s ease;height:100%;border-radius:4px;background:${strength < 34 ? '#ef4444' : strength < 67 ? '#f59e0b' : '#22c55e'}`"></div>
        </div>
        <p style="font-size:0.78rem;margin-top:6px;font-family:var(--font-display);font-weight:600;margin-bottom:0;" :style="`color:${strength < 34 ? '#fca5a5' : strength < 67 ? '#fcd34d' : '#86efac'}`">
            <span x-show="pass.length === 0" style="color:var(--text-muted);font-weight:400;">Kekuatan password</span>
            <span x-show="pass.length > 0 && strength < 34">Lemah — tambahkan huruf besar, angka, & simbol</span>
            <span x-show="strength >= 34 && strength < 67">Sedang — bisa lebih kuat lagi</span>
            <span x-show="strength >= 67">Kuat dan aman 💪</span>
        </p>
    </div>

    {{-- Konfirmasi password --}}
    <div class="mb-4">
        <label class="form-label-sporty" style="margin-bottom: 8px;">Konfirmasi Password</label>
        <div class="premium-input-group" x-data="{ conf: '' }">
            <div class="premium-input-icon">
                <i class="fa-solid fa-shield-check"></i>
            </div>
            <input :type="showConf ? 'text' : 'password'"
                   name="password_confirmation" id="password_confirmation"
                   class="premium-input-field"
                   placeholder="Ulangi password baru"
                   x-model="conf"
                   required>
            <button type="button" class="btn border-0 p-0 position-absolute"
                    style="top:50%;right:16px;transform:translateY(-50%);color:var(--text-muted);background:transparent;z-index:3;width:24px;height:24px;display:flex;align-items:center;justify-content:center;"
                    @click="showConf = !showConf">
                <i :class="showConf ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" style="font-size:0.9rem;"></i>
            </button>
            {{-- Match indicator --}}
            <i x-show="conf.length > 0 && conf === pass"
               class="fa-solid fa-circle-check"
               style="position:absolute;right:48px;top:50%;transform:translateY(-50%);color:#22c55e;font-size:0.95rem;z-index:3;"></i>
            <i x-show="conf.length > 0 && conf !== pass"
               class="fa-solid fa-circle-xmark"
               style="position:absolute;right:48px;top:50%;transform:translateY(-50%);color:#ef4444;font-size:0.95rem;z-index:3;"></i>
        </div>
    </div>

    <button type="submit" class="btn-premium mb-3">
        <span>Simpan Password Baru</span>
        <i class="fa-solid fa-circle-check" style="font-size:0.85rem;"></i>
    </button>
</form>
@endsection

@section('scripts')
<script>
    // Global calcStrength function used in x-data inline
    window.calcStrength = function(pass) {
        if (!pass) return 0;
        let score = 0;
        if (pass.length >= 8)  score += 20;
        if (pass.length >= 12) score += 10;
        if (/[A-Z]/.test(pass)) score += 20;
        if (/[a-z]/.test(pass)) score += 15;
        if (/[0-9]/.test(pass)) score += 20;
        if (/[^A-Za-z0-9]/.test(pass)) score += 15;
        return Math.min(score, 100);
    };
</script>
@endsection
