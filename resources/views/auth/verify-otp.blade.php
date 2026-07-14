@extends('layouts.auth')
@section('title', 'Verifikasi Email — PadelBook')

@section('content')
<div class="text-center mb-4">
    <div style="
        display:inline-flex;align-items:center;justify-content:center;
        width:72px;height:72px;border-radius:50%;margin-bottom:16px;
        background:linear-gradient(135deg,rgba(59,130,246,0.18),rgba(59,130,246,0.06));
        border:2px solid rgba(59,130,246,0.35);
        box-shadow:0 0 28px rgba(59,130,246,0.15);
    ">
        <i class="fa-solid fa-envelope" style="font-size:1.9rem;color:#3b82f6;"></i>
    </div>
    <h2 style="font-family:var(--font-display);font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:6px;">
        Verifikasi Email
    </h2>
    <p style="color:var(--text-muted);font-size:0.88rem;line-height:1.6;">
        Kode OTP 6-digit telah dikirim ke Email<br>
        <strong style="color:#60a5fa;">{{ session('temp_user.email') ?? 'email yang Anda daftarkan' }}</strong>
    </p>
</div>

{{-- Flash info --}}
@if(session('info'))
<div style="margin-bottom:20px;padding:12px 16px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.25);border-radius:12px;display:flex;align-items:center;gap:10px;">
    <i class="fa-solid fa-envelope" style="color:#60a5fa;font-size:1.2rem;"></i>
    <span style="font-size:0.85rem;color:#93c5fd;">{{ session('info') }}</span>
</div>
@endif

<form action="{{ route('otp.verify') }}" method="POST">
    @csrf
    <div style="margin-bottom:28px;">
        <label style="display:block;text-align:center;font-size:0.82rem;color:var(--text-muted);margin-bottom:16px;letter-spacing:0.04em;text-transform:uppercase;">
            Masukkan Kode OTP 6-Digit
        </label>
        <div style="display:flex;gap:10px;justify-content:center;">
            @for($i = 1; $i <= 6; $i++)
            <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]"
                   class="otp-box-reg" id="otp-box-reg-{{ $i }}" autocomplete="off"
                   style="width:52px;height:60px;text-align:center;font-size:1.6rem;font-weight:700;
                          background:rgba(255,255,255,0.05);border:2px solid rgba(255,255,255,0.1);
                          border-radius:14px;color:var(--text-primary);outline:none;
                          transition:all 0.2s ease;font-family:var(--font-display);caret-color:var(--accent);">
            @endfor
        </div>
        <input type="hidden" name="otp" id="otp-hidden-reg">
        <p id="otp-timer-reg" style="text-align:center;margin-top:14px;font-size:0.82rem;color:var(--text-muted);"></p>
    </div>

    <button type="submit" id="btn-verify-reg" class="btn btn-sporty w-100 py-3 mb-3"
            disabled style="opacity:0.5;cursor:not-allowed;">
        <i class="fa-solid fa-circle-check me-2"></i>Verifikasi & Aktifkan Akun
    </button>
</form>

@if($errors->has('otp'))
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:12px;font-size:0.85rem;color:#fca5a5;">
    {{ $errors->first('otp') }}
</div>
@endif

<div style="text-align:center;margin-top:8px;">
    <p style="color:var(--text-muted);font-size:0.83rem;margin:0 0 10px;">
        Tidak menerima kode di Email?
    </p>
    <form action="{{ route('otp.resend') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-ghost btn-sm py-2 px-3" style="font-size:0.85rem;">
            <i class="fa-solid fa-envelope me-1"></i>Kirim Ulang OTP
        </button>
    </form>
    <p style="color:var(--text-muted);font-size:0.78rem;margin:12px 0 0;">
        atau <a href="{{ route('register') }}" style="color:var(--accent);font-weight:600;text-decoration:none;">daftar ulang</a>
    </p>
</div>
@endsection

@section('scripts')
<script>
(function() {
    const boxes  = document.querySelectorAll('.otp-box-reg');
    const hidden = document.getElementById('otp-hidden-reg');
    const btn    = document.getElementById('btn-verify-reg');
    const timer  = document.getElementById('otp-timer-reg');

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
})();
</script>
@endsection
