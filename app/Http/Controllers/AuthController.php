<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\User;
use App\Support\PhoneHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Class AuthController
 * 
 * Menangani seluruh alur autentikasi dan pendaftaran pengguna sistem PadelBook.
 * Mendukung autentikasi berbasis password + OTP WhatsApp, registrasi mandiri
 * dengan verifikasi OTP WA, sistem reset password, integrasi masuk via Google (OAuth2),
 * serta penerbitan token berbasis Sanctum untuk client API external.
 * 
 * @package App\Http\Controllers
 */
class AuthController extends Controller
{
    /**
     * Helper: Mengirim OTP via WhatsApp Kata AI Service.
     * 
     * @param string $phone Nomor tujuan
     * @param string $otpCode Kode OTP
     * @param string $purpose Deskripsi tujuan
     * @return array{ok: bool, error: ?string}
     */
    private function sendWaOtp(string $phone, string $otpCode, string $purpose): array
    {
        return app(\App\Services\NotificationService::class)->sendWhatsAppOtp($phone, $otpCode, $purpose);
    }

    /**
     * Helper: Mengirim OTP via Email (Template HTML).
     * 
     * @param string $toEmail Email tujuan
     * @param string $toName Nama tujuan
     * @param string $otpCode Kode OTP
     * @param string $subject Judul email
     * @param string $purposeText Deskripsi tujuan
     * @param int $expiryMinutes Batas kedaluwarsa OTP dalam menit
     * @return void
     */
    private function sendOtpEmail(
        string $toEmail,
        string $toName,
        string $otpCode,
        string $subject,
        string $purposeText,
        int    $expiryMinutes = 5
    ): void {
        $data = [
            'subject'       => $subject,
            'userName'      => $toName,
            'otpCode'       => $otpCode,
            'purposeText'   => $purposeText,
            'expiryMinutes' => $expiryMinutes,
            'appUrl'        => config('app.url'),
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

    /**
     * Menampilkan Halaman Login Web.
     * 
     * @return View
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Memproses Kredensial Login Pengguna.
     * 
     * Jika admin/super_admin, langsung diarahkan masuk.
     * Jika member/pengguna biasa, kirim kode OTP ke WhatsApp untuk verifikasi tahap dua.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function login(Request $request): RedirectResponse
    {
        // 1. Validasi input email & password
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // 2. Periksa kecocokan kredensial password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Email atau password yang Anda masukkan salah.',
            ])->onlyInput('email');
        }

        // 3. Admin & Super Admin Bypass OTP: langsung login demi efisiensi operasional
        if (in_array($user->role, ['admin', 'super_admin'])) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            // Catat log IP login admin
            LoginLog::create([
                'user_id'    => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method'     => 'password',
                'status'     => 'success',
            ]);

            return redirect()->route('admin.index');
        }

        // 4. Inisialisasi Sesi OTP Dua Faktor (2FA) untuk Member
        $otpCode = rand(100000, 999999);
        session([
            'login_user_id'      => $user->id,
            'login_otp_code'     => $otpCode,
            'login_otp_expires'  => now()->addMinutes(5),
            'login_remember'     => $request->boolean('remember'),
            'login_otp_attempts' => 0,
        ]);

        // 5. Kirim OTP ke Email
        $this->sendOtpEmail(
            $user->email,
            $user->name,
            $otpCode,
            'Kode OTP Login',
            'Anda menerima email ini karena ada percobaan login pada akun PadelBook Anda. Masukkan kode OTP berikut untuk melanjutkan:',
            5
        );
        $message = "Kode OTP telah dikirim ke Email {$user->email}";

        return redirect()->route('login.otp.verify')->with('info', $message);
    }

    /**
     * Menampilkan Halaman Pengisian OTP Login 2FA.
     * 
     * @return RedirectResponse|View
     */
    public function showVerifyLoginOtp(): RedirectResponse|View
    {
        if (!session()->has('login_user_id')) {
            return redirect()->route('login');
        }
        $user = User::find(session('login_user_id'));
        return view('auth.verify-login-otp', compact('user'));
    }

    /**
     * Memverifikasi Kode OTP Login 2FA Pengguna.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function verifyLoginOtp(Request $request): RedirectResponse
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $userId     = session('login_user_id');
        $sessionOtp = session('login_otp_code');
        $expiry     = session('login_otp_expires');
        $remember   = session('login_remember', false);
        $attempts   = session('login_otp_attempts', 0);

        // 1. Cek validitas sesi OTP
        if (!$userId || !$sessionOtp || now()->gt($expiry)) {
            session()->forget(['login_user_id', 'login_otp_code', 'login_otp_expires', 'login_remember', 'login_otp_attempts']);
            return redirect()->route('login')
                ->withErrors(['email' => 'Sesi OTP kedaluwarsa. Silakan login ulang.']);
        }

        // 2. Batasi maksimum 5 kali salah input kode OTP untuk mencegah brute force
        if ($attempts >= 5) {
            session()->forget(['login_user_id', 'login_otp_code', 'login_otp_expires', 'login_remember', 'login_otp_attempts']);
            return redirect()->route('login')
                ->withErrors(['email' => 'Terlalu banyak percobaan OTP yang salah. Silakan login ulang.']);
        }

        // 3. Verifikasi kesesuaian kode OTP
        if ($request->otp !== (string) $sessionOtp) {
            session(['login_otp_attempts' => $attempts + 1]);
            $remaining = 5 - ($attempts + 1);
            return back()->withErrors([
                'otp' => "Kode OTP salah. Sisa percobaan: {$remaining}.",
            ]);
        }

        // 4. OTP Benar, bersihkan sesi OTP dan lakukan login
        $user = User::findOrFail($userId);
        session()->forget(['login_user_id', 'login_otp_code', 'login_otp_expires', 'login_remember', 'login_otp_attempts']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        // 5. Catat log IP login user via OTP
        LoginLog::create([
            'user_id'    => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method'     => 'otp',
            'status'     => 'success',
        ]);

        if ($user->isSuperAdmin() || $user->isOperator()) {
            return redirect('/admin')
                ->with('success', 'Verifikasi OTP berhasil! Selamat datang, ' . $user->name . '!');
        }

        return redirect()->intended('/')
            ->with('success', 'Verifikasi OTP berhasil! Selamat datang kembali, ' . $user->name . '!');
    }

    /**
     * Mengirim Ulang Kode OTP Login.
     * 
     * @return RedirectResponse
     */
    public function resendLoginOtp(): RedirectResponse
    {
        $userId = session('login_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user    = User::findOrFail($userId);
        $otpCode = rand(100000, 999999);
        
        // Reset waktu kedaluwarsa & jumlah hitungan salah
        session([
            'login_otp_code'     => $otpCode,
            'login_otp_expires'  => now()->addMinutes(5),
            'login_otp_attempts' => 0,
        ]);

        $this->sendOtpEmail(
            $user->email,
            $user->name,
            $otpCode,
            'Kode OTP Login (Baru)',
            'Anda meminta kode OTP baru untuk login ke akun PadelBook Anda:',
            5
        );
        $message = "Kode OTP baru telah dikirim ke Email {$user->email}";

        return back()->with('info', $message);
    }

    // ── Register ────────────────────────────────────────────────────────────

    /**
     * Menampilkan Form Registrasi Member Baru.
     * 
     * @return View
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Memproses Registrasi Member Baru.
     * 
     * Memvalidasi input pendaftaran, menaruh data sementara di session,
     * serta memicu pengiriman OTP verifikasi via Email.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function register(Request $request): RedirectResponse
    {
        // 1. Validasi format data pendaftaran
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'phone'    => ['required', 'string', 'max:20', 'regex:/^(\+?62|0)8[0-9]{8,12}$/'],
            'password' => 'required|string|min:8|confirmed',
        ], [
            'phone.regex'     => 'Nomor telepon tidak valid. Gunakan format 08xxxxxxxxxx.',
            'phone.required'  => 'Nomor telepon / WA wajib diisi.',
        ]);

        $phone = PhoneHelper::normalize($request->phone);
        $otpCode = (string) random_int(100000, 999999);

        // 2. Simpan data registrasi sementara ke session untuk menanti verifikasi via Email
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

        // 3. Kirim kode OTP pendaftaran via Email
        $this->sendOtpEmail(
            $request->email,
            $request->name,
            $otpCode,
            'Verifikasi Pendaftaran',
            'Kode OTP ini digunakan untuk memverifikasi pendaftaran akun PadelBook Anda:',
            5
        );

        return redirect()->route('otp.verify')
            ->with('info', 'Kode OTP telah dikirim ke Email ' . $request->email);
    }

    /**
     * Menampilkan Halaman Pengisian OTP Pendaftaran Akun.
     * 
     * @return RedirectResponse|View
     */
    public function showVerifyOtp(): RedirectResponse|View
    {
        if (!session()->has('temp_user')) {
            return redirect()->route('register');
        }
        $phone = PhoneHelper::display(session('temp_user.phone'));
        return view('auth.verify-otp', compact('phone'));
    }

    /**
     * Mengirim Ulang Kode OTP Registrasi Akun.
     * 
     * @return RedirectResponse
     */
    public function resendRegisterOtp(): RedirectResponse
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

        $this->sendOtpEmail(
            $tempUser['email'],
            $tempUser['name'],
            $otpCode,
            'Verifikasi Pendaftaran (Baru)',
            'Kode OTP baru untuk memverifikasi pendaftaran akun PadelBook Anda:',
            5
        );

        return back()->with(
            'info',
            'Kode OTP baru telah dikirim ke Email ' . $tempUser['email']
        );
    }

    /**
     * Memverifikasi Kode OTP Registrasi dan Menyimpan Akun Baru ke Database.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $sessionOtp = session('otp_code');
        $expiry     = session('otp_expires_at');
        $attempts   = session('otp_attempts', 0);

        // 1. Validasi sesi registrasi OTP
        if (!$sessionOtp || now()->gt($expiry)) {
            session()->forget(['temp_user', 'otp_code', 'otp_expires_at', 'otp_attempts']);
            return redirect()->route('register')
                ->withErrors(['email' => 'Sesi OTP kedaluwarsa. Silakan daftar ulang.']);
        }

        // 2. Batasi maksimum 5 kali percobaan
        if ($attempts >= 5) {
            session()->forget(['temp_user', 'otp_code', 'otp_expires_at', 'otp_attempts']);
            return redirect()->route('register')
                ->withErrors(['email' => 'Terlalu banyak percobaan OTP yang salah. Silakan daftar ulang.']);
        }

        // 3. Verifikasi kecocokan OTP
        if ($request->otp !== (string) $sessionOtp) {
            session(['otp_attempts' => $attempts + 1]);
            $remaining = 5 - ($attempts + 1);
            return back()->withErrors(['otp' => "Kode OTP salah. Sisa percobaan: {$remaining}."]);
        }

        // 4. OTP Benar, buat record User permanen di database
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

        return redirect()->intended('/')
            ->with('success', 'Verifikasi Email berhasil! Selamat datang, ' . $user->name . '!');
    }

    // ── Forgot Password via WhatsApp OTP ────────────────────────────────────────

    /**
     * Menampilkan Form Lupa Password.
     * 
     * @return View
     */
    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Memproses Lupa Password dengan Mengirimkan OTP WhatsApp Reset Password.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function processForgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Berikan pesan informatif jika email tidak ditemukan terdaftar
        if (!$user) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Email ini tidak terdaftar. Pastikan email sesuai saat mendaftar.']);
        }

        $otpCode = rand(100000, 999999);

        // 2. Set sesi reset password
        session([
            'reset_user_id'      => $user->id,
            'reset_otp_code'     => $otpCode,
            'reset_otp_expires'  => now()->addMinutes(10),
            'reset_otp_attempts' => 0,
            'reset_otp_verified' => false,
        ]);

        // 3. Kirim OTP via Email
        $this->sendOtpEmail(
            $user->email,
            $user->name,
            $otpCode,
            'Reset Password',
            'Anda meminta untuk mereset password akun PadelBook Anda. Gunakan kode OTP ini:',
            10
        );

        $message = 'Kode OTP reset password telah dikirim ke Email ' . $user->email;

        return redirect()->route('password.reset.otp.verify')->with('info', $message);
    }

    /**
     * Menampilkan Halaman Pengisian OTP Reset Password.
     * 
     * @return RedirectResponse|View
     */
    public function showVerifyResetOtp(): RedirectResponse|View
    {
        if (!session()->has('reset_user_id')) {
            return redirect()->route('password.forgot');
        }
        $user = User::find(session('reset_user_id'));
        return view('auth.verify-reset-otp', compact('user'));
    }

    /**
     * Memverifikasi OTP Reset Password.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function verifyResetOtp(Request $request): RedirectResponse
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $sessionOtp = session('reset_otp_code');
        $expiry     = session('reset_otp_expires');
        $attempts   = session('reset_otp_attempts', 0);

        // 1. Cek validitas sesi reset
        if (!session()->has('reset_user_id') || !$sessionOtp || now()->gt($expiry)) {
            session()->forget(['reset_user_id', 'reset_otp_code', 'reset_otp_expires', 'reset_otp_attempts', 'reset_otp_verified']);
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Sesi OTP kedaluwarsa. Silakan mulai ulang proses reset password.']);
        }

        // 2. Batasi 5 kali kesalahan percobaan OTP
        if ($attempts >= 5) {
            session()->forget(['reset_user_id', 'reset_otp_code', 'reset_otp_expires', 'reset_otp_attempts', 'reset_otp_verified']);
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Terlalu banyak percobaan OTP yang salah. Silakan mulai ulang proses reset password.']);
        }

        // 3. Verifikasi kecocokan OTP
        if ($request->otp !== (string) $sessionOtp) {
            session(['reset_otp_attempts' => $attempts + 1]);
            $remaining = 5 - ($attempts + 1);
            return back()->withErrors([
                'otp' => "Kode OTP salah. Sisa percobaan: {$remaining}.",
            ]);
        }

        // 4. OTP Benar, tandai sukses verifikasi
        session([
            'reset_otp_verified' => true,
        ]);
        session()->forget(['reset_otp_code', 'reset_otp_expires', 'reset_otp_attempts']);

        return redirect()->route('password.reset.form');
    }

    /**
     * Mengirim Ulang Kode OTP Reset Password.
     * 
     * @return RedirectResponse
     */
    public function resendResetOtp(): RedirectResponse
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

        $this->sendOtpEmail(
            $user->email,
            $user->name,
            $otpCode,
            'Reset Password (OTP Baru)',
            'Anda meminta kode OTP baru untuk mereset password akun PadelBook Anda:',
            10
        );

        return back()->with('info', 'Kode OTP baru telah dikirim ke Email ' . $user->email);
    }

    /**
     * Menampilkan Form Pembuatan Password Baru.
     * 
     * @return RedirectResponse|View
     */
    public function showResetPassword(): RedirectResponse|View
    {
        // Proteksi: Pengguna wajib melewati validasi OTP reset terlebih dahulu
        if (!session('reset_otp_verified') || !session('reset_user_id')) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Silakan verifikasi OTP terlebih dahulu.']);
        }
        return view('auth.reset-password');
    }

    /**
     * Memproses Pengubahan Password Baru di Database.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        // Double-check proteksi akses langsung
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

        // Simpan password baru ke database
        $user = User::findOrFail(session('reset_user_id'));
        $user->update(['password' => Hash::make($request->password)]);

        // Bersihkan seluruh session reset password
        session()->forget(['reset_user_id', 'reset_otp_verified']);

        return redirect()->route('login')
            ->with('success', 'Password berhasil direset! Silakan login dengan password baru Anda. 🎉');
    }

    /**
     * Memproses Logout Sesi Pengguna.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('landing');
    }

    // ── Google OAuth ─────────────────────────────────────────────────────────

    /**
     * Mengalihkan Pengguna ke Halaman Pilihan Akun Google.
     * 
     * @return SymfonyRedirectResponse
     */
    public function redirectToGoogle(): SymfonyRedirectResponse
    {
        $driver = \Laravel\Socialite\Facades\Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->stateless();

        if (app()->environment('local')) {
            $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
        }

        return $driver->redirect();
    }

    /**
     * Memproses Callback API Google OAuth.
     * 
     * Membuat akun member otomatis jika belum terdaftar, lalu meloginkan pengguna.
     * 
     * @return RedirectResponse
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $driver = \Laravel\Socialite\Facades\Socialite::driver('google')->stateless();
            
            if (app()->environment('local')) {
                $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            }

            $googleUser = $driver->user();
        } catch (\Exception $e) {
            Log::error('Socialite callback error: ' . $e->getMessage());
            return redirect()->route('login')
                ->withErrors(['email' => 'Gagal login via Google. Silakan coba lagi.']);
        }

        if (empty($googleUser->email)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Gagal login via Google. Silakan coba login manual.']);
        }

        // Buat user baru otomatis jika belum terdaftar (firstOrCreate)
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

        // Catat log IP login via Google
        LoginLog::create([
            'user_id'    => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'method'     => 'google',
            'status'     => 'success',
        ]);

        // Berikan warning jika nomor WhatsApp belum dilengkapi pasca login Google
        if (empty($user->phone)) {
            return redirect()->route('dashboard.profile')
                ->with('warning', 'Login berhasil! Mohon lengkapi Nomor WhatsApp Anda terlebih dahulu agar kami dapat mengirimkan notifikasi reservasi.');
        }

        return redirect()->intended('/')
            ->with('success', 'Berhasil masuk menggunakan akun Google!');
    }

    // ── Sanctum REST API Endpoints ───────────────────────────────────────────

    /**
     * Endpoint API Login (Sanctum).
     * 
     * Mengembalikan plain text token jika kredensial benar.
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function apiLogin(Request $request): JsonResponse
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

    /**
     * Endpoint API Registrasi Member (Sanctum).
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function apiRegister(Request $request): JsonResponse
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

