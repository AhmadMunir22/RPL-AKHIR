<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $subject }}</title>
<style>
  body { margin:0; padding:0; background:#f0f4f8; font-family:'Segoe UI',Arial,sans-serif; }
  .wrapper { max-width:560px; margin:40px auto; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#0b1329 0%,#1a2d52 100%); padding:36px 40px 28px; text-align:center; }
  .header img { height:70px; border-radius:12px; }
  .header h1 { color:#f0f4f8; font-size:1.25rem; font-weight:700; margin:16px 0 0; letter-spacing:0.02em; }
  .body { padding:36px 40px; }
  .greeting { font-size:1rem; color:#2a3d52; margin-bottom:16px; }
  .purpose-text { font-size:0.9rem; color:#5a7080; margin-bottom:28px; line-height:1.6; }
  .otp-box { background:linear-gradient(135deg,#0b1329,#1a2d52); border-radius:14px; padding:28px; text-align:center; margin-bottom:28px; }
  .otp-label { font-size:0.75rem; color:#7a94a8; letter-spacing:0.12em; text-transform:uppercase; margin-bottom:12px; }
  .otp-code { font-size:3rem; font-weight:800; color:#e07a5f; letter-spacing:0.3em; font-family:monospace; margin:0; }
  .expiry { display:inline-block; background:rgba(224,122,95,0.12); color:#e07a5f; border:1px solid rgba(224,122,95,0.3); border-radius:8px; padding:8px 16px; font-size:0.82rem; font-weight:600; margin-bottom:28px; }
  .warning-box { background:#fff8f6; border:1px solid #fde8e0; border-radius:10px; padding:16px; margin-bottom:24px; }
  .warning-box p { margin:4px 0; font-size:0.83rem; color:#7a4030; }
  .warning-box strong { color:#c05a3f; }
  .footer { background:#f8fafc; border-top:1px solid #e8edf2; padding:24px 40px; text-align:center; }
  .footer p { font-size:0.78rem; color:#9aabb8; margin:4px 0; line-height:1.6; }
  @media (max-width:600px) {
    .body, .footer { padding:28px 24px; }
    .otp-code { font-size:2.2rem; letter-spacing:0.2em; }
  }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <img src="{{ $appUrl }}/images/logo.jpeg" alt="PadelBook Logo">
    <h1>{{ $subject }}</h1>
  </div>

  <div class="body">
    <p class="greeting">Halo, <strong>{{ $userName }}</strong> 👋</p>
    <p class="purpose-text">{{ $purposeText }}</p>

    <div class="otp-box">
      <p class="otp-label">Kode OTP Anda</p>
      <p class="otp-code">{{ $otpCode }}</p>
    </div>

    <div style="text-align:center;margin-bottom:24px;">
      <span class="expiry">
        ⏱ Berlaku selama {{ $expiryMinutes }} menit
      </span>
    </div>

    <div class="warning-box">
      <p>🔒 <strong>Jangan bagikan kode ini kepada siapa pun</strong>, termasuk tim PadelBook.</p>
      <p>⚠️ Jika Anda tidak melakukan permintaan ini, abaikan email ini.</p>
      <p>🛡️ Kode akan otomatis kedaluwarsa dan tidak dapat digunakan kembali.</p>
    </div>
  </div>

  <div class="footer">
    <p>Email ini dikirim secara otomatis oleh <strong>PadelBook</strong>.</p>
    <p>Tolong jangan membalas email ini.</p>
    <p style="margin-top:12px;color:#c0cdd8;">© {{ date('Y') }} PadelBook. All rights reserved.</p>
  </div>
</div>
</body>
</html>
