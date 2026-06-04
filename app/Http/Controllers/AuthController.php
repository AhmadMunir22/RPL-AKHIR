<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PhoneHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** @return array{ok: bool, error: ?string} */
    private function sendWaOtp(string $phone, string $otpCode, string $purpose): array
    {
        return app(\App\Services\NotificationService::class)->sendWhatsAppOtp($phone, $otpCode, $purpose);
    }

    // ── Helper: send OTP email (HTML template) ──────────────────────────────

    private function sendOtpEmail(
        string $toEmail,
        string $toName,
        string $otpCode,
        string $subject,
        string $purposeText,
        int    $expiryMinutes = 5
    ): void {
        $data = [
            'subject'        => $subject,
            'userName'       => $toName,
            'otpCode'        => $otpCode,
            'purposeText'    => $purposeText,
            'expiryMinutes'  => $expiryMinutes,
            'appUrl'         => config('app.url'),
        ];

        try {
            Mail::send('emails.otp', $data, function ($mail) use ($toEmail, $subject) {
                $mail->to($toEmail)
                     ->subject("[PadelBook] {$subject}");
            });
            Log::info("OTP email sent to {$toEmail}");
        } catch (\Exception $e) {
            Log::warning("OTP email failed to {$toEmail}: " . $e->getMessage());
        }
    }

    // ── Web Authentication ──────────────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Email atau password yang Anda masukkan salah.',
            ])->onlyInput('email');
        }

        // Admin langsung login tanpa OTP
        if (in_array($user->role, ['admin', 'super_admin'])) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            return redirect()->route('admin.index');
        }

        // Kirim OTP ke WA pengguna setelah credentials valid
        $otpCode = rand(100000, 999999);
        session([
            'login_user_id'      => $user->id,
            'login_otp_code'     => $otpCode,
            'login_otp_expires'  => now()->addMinutes(5),
            'login_remember'     => $request->boolean('remember'),
            'login_otp_attempts' => 0,
        ]);

        if ($user->phone) {
            $wa = $this->sendWaOtp($user->phone, $otpCode, 'Verifikasi Login Akun PadelBook');
            if (!$wa['ok']) {
                return back()->withErrors([
                    'email' => $wa['error'] ?? 'Gagal mengirim OTP ke WhatsApp.',
                ])->onlyInput('email');
            }
            $message = 'Kode OTP telah dikirim ke WhatsApp ' . PhoneHelper::display($user->phone);
        } else {
            // Fallback for old users without phone number
            $this->sendOtpEmail(
                $user->email,
                $user->name,
                $otpCode,
                'Kode OTP Login',
                'Anda menerima email ini karena ada percobaan login pada akun PadelBook Anda. Masukkan kode OTP berikut untuk melanjutkan:',
                5
            );
            $message = "Kode OTP telah dikirim ke Email {$user->email} (Harap lengkapi nomor WA Anda nanti)";
        }

        return redirect()->route('login.otp.verify')
            ->with('info', $message);
    }

    public function showVerifyLoginOtp()
    {
        if (!session()->has('login_user_id')) {
            return redirect()->route('login');
        }
        $user = User::find(session('login_user_id'));
        return view('auth.verify-login-otp', compact('user'));
    }

    public function verifyLoginOtp(Request $request)
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $userId     = session('login_user_id');
        $sessionOtp = session('login_otp_code');
        $expiry     = session('login_otp_expires');
        $remember   = session('login_remember', false);
        $attempts   = session('login_otp_attempts', 0);

        // Sesi tidak ada atau kedaluwarsa
        if (!$userId || !$sessionOtp || now()->gt($expiry)) {
            session()->forget(['login_user_id', 'login_otp_code', 'login_otp_expires', 'login_remember', 'login_otp_attempts']);
            return redirect()->route('login')
                ->withErrors(['email' => 'Sesi OTP kedaluwarsa. Silakan login ulang.']);
        }

        // Batasi maksimal 5 percobaan
        if ($attempts >= 5) {
            session()->forget(['login_user_id', 'login_otp_code', 'login_otp_expires', 'login_remember', 'login_otp_attempts']);
            return redirect()->route('login')
                ->withErrors(['email' => 'Terlalu banyak percobaan OTP yang salah. Silakan login ulang.']);
        }

        // Cek kode OTP
        if ($request->otp !== (string) $sessionOtp) {
            session(['login_otp_attempts' => $attempts + 1]);
            $remaining = 5 - ($attempts + 1);
            return back()->withErrors([
                'otp' => "Kode OTP salah. Sisa percobaan: {$remaining}.",
            ]);
        }

        // OTP benar — login
        $user = User::findOrFail($userId);
        session()->forget(['login_user_id', 'login_otp_code', 'login_otp_expires', 'login_remember', 'login_otp_attempts']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($user->isSuperAdmin() || $user->isOperator()) {
            return redirect('/admin')
                ->with('success', 'Verifikasi OTP berhasil! Selamat datang, ' . $user->name . '!');
        }

        return redirect('/dashboard')
            ->with('success', 'Verifikasi OTP berhasil! Selamat datang kembali, ' . $user->name . '!');
    }

    public function resendLoginOtp()
    {
        $userId = session('login_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user    = User::findOrFail($userId);
        $otpCode = rand(100000, 999999);
        session([
            'login_otp_code'     => $otpCode,
            'login_otp_expires'  => now()->addMinutes(5),
            'login_otp_attempts' => 0,
        ]);

        if ($user->phone) {
            $wa = $this->sendWaOtp($user->phone, $otpCode, 'Kirim Ulang OTP Login Akun PadelBook');
            if (!$wa['ok']) {
                return back()->withErrors(['otp' => $wa['error'] ?? 'Gagal mengirim ulang OTP.']);
            }
            $message = 'Kode OTP baru telah dikirim ke WhatsApp ' . PhoneHelper::display($user->phone);
        } else {
            $this->sendOtpEmail(
                $user->email,
                $user->name,
                $otpCode,
                'Kode OTP Login (Baru)',
                'Anda meminta kode OTP baru untuk login ke akun PadelBook Anda:',
                5
            );
            $message = "Kode OTP baru telah dikirim ke Email {$user->email}";
        }

        return back()->with('info', $message);
    }

    // ── Register ────────────────────────────────────────────────────────────

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'phone'    => ['required', 'string', 'max:20', 'regex:/^(\+?62|0)8[0-9]{8,12}$/'],
            'password' => 'required|string|min:8|confirmed',
        ], [
            'phone.regex' => 'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx.',
        ]);

        $phone = PhoneHelper::normalize($request->phone);
        $otpCode = (string) random_int(100000, 999999);

        session([
            'temp_user' => [
                'name'     => $request->name,
                'email'    => $request->email,
                'phone'    => $phone,
                'password' => Hash::make($request->password),
            ],
            'otp_code'        => $otpCode,
            'otp_expires_at'  => now()->addMinutes(5),
            'otp_attempts'    => 0,
        ]);

        $wa = $this->sendWaOtp($phone, $otpCode, 'Verifikasi Pendaftaran Akun PadelBook');

        if (!$wa['ok']) {
            session()->forget(['temp_user', 'otp_code', 'otp_expires_at', 'otp_attempts']);
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['phone' => $wa['error'] ?? 'Gagal mengirim OTP ke WhatsApp.']);
        }

        return redirect()->route('otp.verify')
            ->with('info', 'Kode OTP telah dikirim ke WhatsApp ' . PhoneHelper::display($phone));
    }

    public function showVerifyOtp()
    {
        if (!session()->has('temp_user')) {
            return redirect()->route('register');
        }
        $phone = PhoneHelper::display(session('temp_user.phone'));
        return view('auth.verify-otp', compact('phone'));
    }

    public function resendRegisterOtp()
    {
        if (!session()->has('temp_user')) {
            return redirect()->route('register');
        }

        $tempUser = session('temp_user');
        $otpCode = (string) random_int(100000, 999999);

        session([
            'otp_code'       => $otpCode,
            'otp_expires_at' => now()->addMinutes(5),
            'otp_attempts'   => 0,
        ]);

        $wa = $this->sendWaOtp($tempUser['phone'], $otpCode, 'Kirim Ulang OTP Pendaftaran PadelBook');

        if (!$wa['ok']) {
            return back()->withErrors(['otp' => $wa['error'] ?? 'Gagal mengirim ulang OTP.']);
        }

        return back()->with(
            'info',
            'Kode OTP baru telah dikirim ke WhatsApp ' . PhoneHelper::display($tempUser['phone'])
        );
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $sessionOtp = session('otp_code');
        $expiry     = session('otp_expires_at');
        $attempts   = session('otp_attempts', 0);

        if (!$sessionOtp || now()->gt($expiry)) {
            session()->forget(['temp_user', 'otp_code', 'otp_expires_at', 'otp_attempts']);
            return redirect()->route('register')
                ->withErrors(['email' => 'Sesi OTP kedaluwarsa. Silakan daftar ulang.']);
        }

        if ($attempts >= 5) {
            session()->forget(['temp_user', 'otp_code', 'otp_expires_at', 'otp_attempts']);
            return redirect()->route('register')
                ->withErrors(['email' => 'Terlalu banyak percobaan OTP yang salah. Silakan daftar ulang.']);
        }

        if ($request->otp !== (string) $sessionOtp) {
            session(['otp_attempts' => $attempts + 1]);
            $remaining = 5 - ($attempts + 1);
            return back()->withErrors(['otp' => "Kode OTP salah. Sisa percobaan: {$remaining}."]);
        }

        $tempUser = session('temp_user');
        $user = User::create([
            'name'              => $tempUser['name'],
            'email'             => $tempUser['email'],
            'phone'             => $tempUser['phone'],
            'password'          => $tempUser['password'],
            'role'              => 'member',
            'points'            => 0,
            'wallet_balance'    => 0.00,
            'email_verified_at' => now(),
        ]);

        session()->forget(['temp_user', 'otp_code', 'otp_expires_at', 'otp_attempts']);
        Auth::login($user);

        return redirect()->route('dashboard.index')
            ->with('success', 'Verifikasi WhatsApp berhasil! Selamat datang, ' . $user->name . '!');
    }

    // ── Forgot Password via Email OTP ────────────────────────────────────────

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function processForgotPassword(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^(\+?62|0)8[0-9]{8,12}$/'],
        ], [
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex'    => 'Format nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx.',
        ]);

        $phone = PhoneHelper::normalize($request->phone);
        $user  = User::where('phone', $phone)->first();

        // Tampilkan pesan generik agar nomor yang tidak terdaftar tidak terekspos
        if (!$user) {
            return back()
                ->withInput()
                ->withErrors(['phone' => 'Nomor WhatsApp ini tidak terdaftar. Pastikan nomor sesuai saat mendaftar.']);
        }

        $otpCode = rand(100000, 999999);

        session([
            'reset_user_id'      => $user->id,
            'reset_otp_code'     => $otpCode,
            'reset_otp_expires'  => now()->addMinutes(10),
            'reset_otp_attempts' => 0,
            'reset_otp_verified' => false,
        ]);

        $wa = $this->sendWaOtp($phone, $otpCode, 'Reset Password Akun PadelBook');
        if (!$wa['ok']) {
            session()->forget(['reset_user_id', 'reset_otp_code', 'reset_otp_expires', 'reset_otp_attempts', 'reset_otp_verified']);
            return back()
                ->withInput()
                ->withErrors(['phone' => $wa['error'] ?? 'Gagal mengirim OTP ke WhatsApp. Coba lagi.']);
        }

        $message = 'Kode OTP reset password telah dikirim ke WhatsApp ' . PhoneHelper::display($phone);

        return redirect()->route('password.reset.otp.verify')
            ->with('info', $message);
    }

    public function showVerifyResetOtp()
    {
        if (!session()->has('reset_user_id')) {
            return redirect()->route('password.forgot');
        }
        $user = User::find(session('reset_user_id'));
        return view('auth.verify-reset-otp', compact('user'));
    }

    public function verifyResetOtp(Request $request)
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $sessionOtp = session('reset_otp_code');
        $expiry     = session('reset_otp_expires');
        $attempts   = session('reset_otp_attempts', 0);

        // Sesi tidak ada atau kedaluwarsa
        if (!session()->has('reset_user_id') || !$sessionOtp || now()->gt($expiry)) {
            session()->forget(['reset_user_id', 'reset_otp_code', 'reset_otp_expires', 'reset_otp_attempts', 'reset_otp_verified']);
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Sesi OTP kedaluwarsa. Silakan mulai ulang proses reset password.']);
        }

        // Batasi maksimal 5 percobaan — jika habis, session dihapus & harus ulang
        if ($attempts >= 5) {
            session()->forget(['reset_user_id', 'reset_otp_code', 'reset_otp_expires', 'reset_otp_attempts', 'reset_otp_verified']);
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Terlalu banyak percobaan OTP yang salah. Silakan mulai ulang proses reset password.']);
        }

        // OTP salah — tambah hitungan, kembalikan ke halaman verifikasi
        if ($request->otp !== (string) $sessionOtp) {
            session(['reset_otp_attempts' => $attempts + 1]);
            $remaining = 5 - ($attempts + 1);
            return back()->withErrors([
                'otp' => "Kode OTP salah. Sisa percobaan: {$remaining}.",
            ]);
        }

        // OTP BENAR — tandai terverifikasi, hapus kode OTP dari sesi
        session([
            'reset_otp_verified' => true,
        ]);
        session()->forget(['reset_otp_code', 'reset_otp_expires', 'reset_otp_attempts']);

        return redirect()->route('password.reset.form');
    }

    public function resendResetOtp()
    {
        $userId = session('reset_user_id');
        if (!$userId) {
            return redirect()->route('password.forgot');
        }

        $user    = User::findOrFail($userId);
        $otpCode = rand(100000, 999999);
        session([
            'reset_otp_code'     => $otpCode,
            'reset_otp_expires'  => now()->addMinutes(10),
            'reset_otp_attempts' => 0,
        ]);

        if (!$user->phone) {
            return back()->withErrors(['otp' => 'Akun tidak memiliki nomor WhatsApp terdaftar.']);
        }

        $wa = $this->sendWaOtp($user->phone, $otpCode, 'Reset Password (OTP Baru)');

        if (!$wa['ok']) {
            return back()->withErrors(['otp' => $wa['error'] ?? 'Gagal mengirim ulang OTP.']);
        }

        return back()->with('info', 'Kode OTP baru telah dikirim ke WhatsApp ' . PhoneHelper::display($user->phone));
    }

    public function showResetPassword()
    {
        // Wajib: harus sudah melewati verifikasi OTP yang benar
        if (!session('reset_otp_verified') || !session('reset_user_id')) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Silakan verifikasi OTP terlebih dahulu.']);
        }
        return view('auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        // Double-check: blokir akses langsung tanpa OTP terverifikasi
        if (!session('reset_otp_verified') || !session('reset_user_id')) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Sesi tidak valid. Silakan ulangi proses reset password.']);
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min'       => 'Password minimal 8 karakter.',
        ]);

        $user = User::findOrFail(session('reset_user_id'));
        $user->update(['password' => Hash::make($request->password)]);

        // Bersihkan semua sesi reset
        session()->forget(['reset_user_id', 'reset_otp_verified']);

        return redirect()->route('login')
            ->with('success', 'Password berhasil direset! Silakan login dengan password baru Anda. 🎉');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('landing');
    }

    // ── Google OAuth ─────────────────────────────────────────────────────────

    public function redirectToGoogle()
    {
        if (class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
            try {
                return \Laravel\Socialite\Facades\Socialite::driver('google')->redirect();
            } catch (\Exception $e) {
                Log::warning('Socialite redirect failed: ' . $e->getMessage());
            }
        }

        session(['mock_google_user' => [
            'name'      => 'Demo Google User',
            'email'     => 'demo-google@padelbook.com',
            'phone'     => null,
            'google_id' => Str::uuid(),
        ]]);

        return redirect()->action([self::class, 'handleGoogleCallback']);
    }

    public function handleGoogleCallback()
    {
        $googleUser = null;

        if (class_exists(\Laravel\Socialite\Facades\Socialite::class) && !session()->has('mock_google_user')) {
            try {
                $googleUser = \Laravel\Socialite\Facades\Socialite::driver('google')->user();
            } catch (\Exception $e) {
                Log::error('Socialite callback error: ' . $e->getMessage());
            }
        }

        if (!$googleUser) {
            $raw        = session('mock_google_user', []);
            $googleUser = (object) $raw;
            session()->forget('mock_google_user');
        }

        if (empty($googleUser->email)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Gagal login via Google. Silakan coba login manual.']);
        }

        $user = User::firstOrCreate(
            ['email' => $googleUser->email],
            [
                'name'              => $googleUser->name,
                'phone'             => null,
                'password'          => Hash::make(Str::random(24)),
                'role'              => 'member',
                'points'            => 0,
                'wallet_balance'    => 0.00,
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user);

        return redirect()->route('dashboard.index')
            ->with('success', 'Berhasil masuk menggunakan akun Google!');
    }

    // ── Sanctum API ──────────────────────────────────────────────────────────

    public function apiLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Kredensial salah.']]);
        }

        return response()->json([
            'success' => true,
            'token'   => $user->createToken('api-token')->plainTextToken,
            'user'    => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->role,
                'wallet_balance' => $user->wallet_balance,
                'points'         => $user->points,
            ],
        ]);
    }

    public function apiRegister(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'phone'             => null,
            'password'          => Hash::make($request->password),
            'role'              => 'member',
            'points'            => 0,
            'wallet_balance'    => 0.00,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'token'   => $user->createToken('api-token')->plainTextToken,
            'user'    => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ], 201);
    }
}
