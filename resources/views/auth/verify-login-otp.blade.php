@extends('layouts.auth')
@section('title', 'Verifikasi OTP Login — PadelBook')

@section('content')
<div class="text-center mb-4">
    <div style="
        display:inline-flex;align-items:center;justify-content:center;
        width:72px;height:72px;border-radius:50%;margin-bottom:16px;
        background:linear-gradient(135deg,rgba(224,122,95,0.18),rgba(224,122,95,0.06));
        border:2px solid rgba(224,122,95,0.35);
        box-shadow:0 0 28px rgba(224,122,95,0.15);
    ">
        <i class="fa-solid fa-envelope-circle-check" style="font-size:1.8rem;color:var(--accent);"></i>
    </div>
    <h2 style="font-family:var(--font-display);font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:6px;">
        Verifikasi OTP Login
    </h2>
    <p style="color:var(--text-muted);font-size:0.88rem;line-height:1.5;">
        Kode OTP 6-digit telah dikirim ke<br>
        <strong style="color:var(--text-secondary);">
            @if(isset($user))
                {{ substr($user->email, 0, 3) }}***@{{ explode('@', $user->email)[1] }}
            @else
                email terdaftar Anda
            @endif
        </strong>
    </p>
</div>

{{-- Flash info --}}
@if(session('info'))
<div style="margin-bottom:20px;padding:12px 16px;background:rgba(224,122,95,0.08);border:1px solid rgba(224,122,95,0.25);border-radius:12px;display:flex;align-items:center;gap:10px;">
    <i class="fa-solid fa-envelope" style="color:var(--accent);font-size:1rem;"></i>
    <span style="font-size:0.85rem;color:var(--accent-light);">{{ session('info') }}</span>
</div>
@endif

<form action="{{ route('login.otp.verify') }}" method="POST" id="otp-login-form">
    @csrf
    <div style="margin-bottom:28px;">
        <label style="display:block;text-align:center;font-size:0.82rem;color:var(--text-muted);margin-bottom:16px;letter-spacing:0.04em;text-transform:uppercase;">
            Masukkan Kode OTP
        </label>
        <div style="display:flex;gap:10px;justify-content:center;">
            @for($i = 1; $i <= 6; $i++)
            <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]"
                   class="otp-box" id="otp-box-{{ $i }}" autocomplete="off"
                   style="width:52px;height:60px;text-align:center;font-size:1.6rem;font-weight:700;
                          background:rgba(255,255,255,0.05);border:2px solid rgba(255,255,255,0.1);
                          border-radius:14px;color:var(--text-primary);outline:none;
                          transition:all 0.2s ease;font-family:var(--font-display);caret-color:var(--accent);">
            @endfor
        </div>
        <input type="hidden" name="otp" id="otp-hidden-login">
        <p id="otp-timer-login" style="text-align:center;margin-top:14px;font-size:0.82rem;color:var(--text-muted);"></p>
    </div>

    <button type="submit" id="btn-verify-login" class="btn btn-sporty w-100 py-3 mb-3"
            disabled style="opacity:0.5;cursor:not-allowed;">
        <i class="fa-solid fa-shield-halved me-2"></i>Verifikasi & Masuk
    </button>
</form>

<form action="{{ route('login.otp.resend') }}" method="POST">
    @csrf
    <button type="submit" id="btn-resend-login" class="btn w-100 py-2"
            style="background:transparent;border:1px solid rgba(255,255,255,0.1);color:var(--text-muted);border-radius:12px;font-size:0.85rem;"
            disabled>
        <i class="fa-solid fa-rotate-right me-2"></i>
        <span id="resend-login-text">Kirim Ulang OTP</span>
    </button>
</form>

<div style="text-align:center;margin-top:20px;">
    <a href="{{ route('login') }}" style="font-size:0.83rem;color:var(--text-muted);text-decoration:none;">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Login
    </a>
</div>
@endsection

@section('scripts')
<script>
(function() {
    const boxes     = document.querySelectorAll('.otp-box');
    const hidden    = document.getElementById('otp-hidden-login');
    const btn       = document.getElementById('btn-verify-login');
    const timer     = document.getElementById('otp-timer-login');
    const resend    = document.getElementById('btn-resend-login');
    const resendTxt = document.getElementById('resend-login-text');

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
        box.addEventListener('focus', () => {
            box.style.borderColor='var(--accent)';
            box.style.background='rgba(224,122,95,0.07)';
            box.style.boxShadow='0 0 0 3px rgba(224,122,95,0.15)';
        });
        box.addEventListener('blur', () => {
            box.style.borderColor=box.value?'rgba(255,255,255,0.25)':'rgba(255,255,255,0.1)';
            box.style.background=box.value?'rgba(255,255,255,0.08)':'rgba(255,255,255,0.05)';
            box.style.boxShadow='none';
        });
    });

    function updateHidden() {
        const code = [...boxes].map(b=>b.value).join('');
        hidden.value = code;
        const ok = /^\d{6}$/.test(code);
        btn.disabled = !ok;
        btn.style.opacity = ok?'1':'0.5';
        btn.style.cursor  = ok?'pointer':'not-allowed';
    }

    boxes[0].focus();

    let sec = 300;
    (function tick() {
        const m=String(Math.floor(sec/60)).padStart(2,'0'), s=String(sec%60).padStart(2,'0');
        timer.innerHTML = sec>0
            ? `<i class="fa-regular fa-clock me-1"></i>Kode kedaluwarsa dalam <strong style="color:var(--accent);">${m}:${s}</strong>`
            : `<span style="color:#fca5a5;"><i class="fa-solid fa-circle-exclamation me-1"></i>Kode OTP telah kedaluwarsa.</span>`;
        if(sec>0){sec--;setTimeout(tick,1000);}else{btn.disabled=true;btn.style.opacity='0.4';}
    })();

    let rcd = 60;
    (function resendTick() {
        resendTxt.textContent = rcd>0 ? `Kirim Ulang (${rcd}s)` : 'Kirim Ulang OTP';
        resend.disabled = rcd>0;
        resend.style.opacity = rcd>0?'0.5':'1';
        if(rcd>0){rcd--;setTimeout(resendTick,1000);}
    })();
})();
</script>
@endsection
