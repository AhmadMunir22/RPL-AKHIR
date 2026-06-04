@extends('layouts.auth')
@section('title', 'Verifikasi OTP Reset Password — PadelBook')
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

/* Premium OTP Box Styling */
.otp-box-reset {
    width: 48px;
    height: 56px;
    text-align: center;
    font-size: 1.6rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.02) !important;
    border: 1.5px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 14px;
    color: var(--text-primary) !important;
    outline: none;
    transition: all 0.25s var(--ease-smooth);
    font-family: var(--font-display);
    caret-color: var(--accent);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15);
}
.otp-box-reset:focus {
    border-color: var(--accent) !important;
    background: rgba(224, 122, 95, 0.05) !important;
    box-shadow: 0 0 0 4px var(--accent-glow), inset 0 2px 4px rgba(0, 0, 0, 0.05) !important;
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
.btn-premium:hover:not(:disabled) {
    background: linear-gradient(135deg, var(--accent-light), var(--accent));
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(224, 122, 95, 0.45);
}
.btn-premium:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 8px var(--accent-glow);
}
.btn-premium:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@keyframes pulse-whatsapp {
    0%, 100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.4); transform: scale(1); }
    50% { box-shadow: 0 0 20px rgba(74, 222, 128, 0.2); transform: scale(1.05); }
}
.wa-icon-glow {
    animation: pulse-whatsapp 3s infinite ease-in-out;
}

/* Suppress redundant global layout errors */
.auth-card > div:has(ul) {
    display: none !important;
}
</style>

{{-- Icon + Heading --}}
<div class="text-center mb-4">
    <div class="wa-icon-glow" style="display:inline-flex;align-items:center;justify-content:center;
        width:68px;height:68px;border-radius:50%;margin-bottom:16px;
        background:linear-gradient(135deg,rgba(74,222,128,0.18),rgba(74,222,128,0.05));
        border:2px solid rgba(74,222,128,0.4);">
        <i class="fa-brands fa-whatsapp" style="font-size:2rem;color:#4ade80;"></i>
    </div>
    <h2 style="font-family:var(--font-display);font-size:1.55rem;font-weight:800;color:var(--text-primary);margin-bottom:8px;letter-spacing:-0.01em;">
        Verifikasi OTP
    </h2>
    <p style="color:var(--text-secondary);font-size:0.88rem;line-height:1.55;margin:0;max-width:320px;margin-left:auto;margin-right:auto;">
        Kode OTP telah dikirim ke nomor WhatsApp:<br>
        <strong style="color:#4ade80;font-weight:700;">
            @if(isset($user) && $user->phone)
                {{ \App\Support\PhoneHelper::display($user->phone) }}
            @elseif(isset($user))
                {{ substr($user->email, 0, 3) }}***@{{ explode('@', $user->email)[1] }}
            @else
                WhatsApp terdaftar Anda
            @endif
        </strong>
    </p>
</div>

{{-- Step indicator --}}
<div class="steps-wrapper">
    <!-- Background progress line -->
    <div style="position: absolute; top: 16px; left: 15%; right: 15%; height: 2px; background: rgba(255,255,255,0.08); z-index: 1;">
        <div style="width: 50%; height: 100%; background: linear-gradient(90deg, #4ade80, var(--accent)); transition: width 0.3s ease;"></div>
    </div>
    
    <!-- Step 1 completed -->
    <div class="step-item completed">
        <div class="step-circle">
            <i class="fa-solid fa-check" style="font-size:0.8rem;"></i>
        </div>
        <span class="step-label">No. WA</span>
    </div>
    
    <!-- Step 2 active -->
    <div class="step-item active">
        <div class="step-circle">2</div>
        <span class="step-label">Verifikasi</span>
    </div>
    
    <!-- Step 3 inactive -->
    <div class="step-item">
        <div class="step-circle">3</div>
        <span class="step-label">Password</span>
    </div>
</div>

{{-- Flash info --}}
@if(session('info'))
<div style="margin-bottom:20px;padding:12px 16px;background:rgba(74,222,128,0.06);border:1px solid rgba(74,222,128,0.2);border-radius:12px;display:flex;align-items:center;gap:10px;">
    <i class="fa-brands fa-whatsapp" style="color:#4ade80;font-size:1.1rem;flex-shrink:0;"></i>
    <span style="font-size:0.82rem;color:#86efac;font-weight:500;">{{ session('info') }}</span>
</div>
@endif

<form action="{{ route('password.reset.otp.verify') }}" method="POST">
    @csrf
    <div style="margin-bottom:28px;">
        <label class="form-label-sporty" style="text-align:center;margin-bottom:16px;">
            Masukkan 6 Digit Kode OTP
        </label>
        <div style="display:flex;gap:10px;justify-content:center;">
            @for($i = 1; $i <= 6; $i++)
            <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]"
                   class="otp-box-reset" id="otp-box-reset-{{ $i }}" autocomplete="off" required>
            @endfor
        </div>
        <input type="hidden" name="otp" id="otp-hidden-reset">
        
        <p id="otp-timer-reset" style="text-align:center;margin-top:16px;font-size:0.82rem;color:var(--text-muted);display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:0;"></p>
    </div>

    <button type="submit" id="btn-verify-reset" class="btn-premium mb-3" disabled>
        <span>Verifikasi Kode OTP</span>
        <i class="fa-solid fa-shield-halved" style="font-size:0.85rem;"></i>
    </button>
</form>

<form action="{{ route('password.reset.otp.resend') }}" method="POST">
    @csrf
    <button type="submit" id="btn-resend-reset" class="btn w-100 py-2.5"
            style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.08);color:var(--text-secondary);border-radius:12px;font-size:0.85rem;font-weight:600;font-family:var(--font-display);transition:var(--transition-fast);"
            onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='rgba(255,255,255,0.15)';"
            onmouseout="this.style.background='rgba(255,255,255,0.02)'; this.style.borderColor='rgba(255,255,255,0.08)';"
            disabled>
        <i class="fa-solid fa-rotate-right me-2"></i>
        <span id="resend-reset-text">Kirim Ulang OTP</span>
    </button>
</form>

<div style="text-align:center;margin-top:24px;">
    <a href="{{ route('password.forgot') }}" style="font-size:0.85rem;color:var(--text-muted);text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:var(--transition-fast);"
       onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-muted)'">
        <i class="fa-solid fa-arrow-left" style="font-size:0.75rem;"></i> Kembali
    </a>
</div>

@endsection

@section('scripts')
<script>
(function() {
    const boxes     = document.querySelectorAll('.otp-box-reset');
    const hidden    = document.getElementById('otp-hidden-reset');
    const btn       = document.getElementById('btn-verify-reset');
    const timer     = document.getElementById('otp-timer-reset');
    const resend    = document.getElementById('btn-resend-reset');
    const resendTxt = document.getElementById('resend-reset-text');

    boxes.forEach((box, idx) => {
        box.addEventListener('input', e => {
            box.value = e.target.value.replace(/\D/g,'').slice(-1);
            updateHidden();
            if (box.value && idx < 5) boxes[idx+1].focus();
        });
        box.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !box.value && idx > 0) boxes[idx-1].focus();
        });
        box.addEventListener('paste', e => {
            e.preventDefault();
            const paste = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'');
            paste.split('').slice(0,6).forEach((ch,i)=>{ if(boxes[i]) boxes[i].value=ch; });
            updateHidden();
            const nx = [...boxes].findIndex(b=>!b.value);
            (nx !== -1 ? boxes[nx] : boxes[5]).focus();
        });
    });

    function updateHidden() {
        const code = [...boxes].map(b=>b.value).join('');
        hidden.value = code;
        const ok = /^\d{6}$/.test(code);
        btn.disabled = !ok;
    }

    boxes[0].focus();

    let sec = 600;
    (function tick() {
        const m=String(Math.floor(sec/60)).padStart(2,'0'), s=String(sec%60).padStart(2,'0');
        timer.innerHTML = sec>0
            ? `<i class="fa-regular fa-clock" style="color:var(--accent);font-size:0.85rem;margin-top:1px;"></i>Kode kedaluwarsa dalam <strong style="color:var(--accent);">${m}:${s}</strong>`
            : `<i class="fa-solid fa-circle-exclamation" style="color:#ef4444;font-size:0.85rem;margin-top:1px;"></i><span style="color:#fca5a5;">Kode OTP telah kedaluwarsa.</span>`;
        if(sec>0){sec--;setTimeout(tick,1000);}else{btn.disabled=true;}
    })();

    let rcd = 60;
    (function resendTick() {
        resendTxt.textContent = rcd>0 ? `Kirim Ulang (${rcd}s)` : 'Kirim Ulang ke WhatsApp';
        resend.disabled = rcd>0;
        resend.style.opacity = rcd>0?'0.5':'1';
        if(rcd>0){rcd--;setTimeout(resendTick,1000);}
    })();
})();
</script>
@endsection
