<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::get('/', [CourtController::class, 'landing'])->name('landing');
Route::get('/courts', [CourtController::class, 'index'])->name('courts.index');
Route::get('/courts/{id}/availability', [CourtController::class, 'availability'])->name('courts.availability');
Route::get('/courts/{id}/reviews', [CourtController::class, 'apiReviews'])->name('courts.reviews');
Route::get('/live-availability', [CourtController::class, 'liveAvailability'])->name('courts.live-availability');
Route::get('/courts/{id}', [CourtController::class, 'show'])->name('courts.show');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    
    // WhatsApp OTP Verification (Register)
    Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('otp.verify');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/verify-otp/resend', [AuthController::class, 'resendRegisterOtp'])->name('otp.resend');

    // Login OTP Verification
    Route::get('/login/verify-otp', [AuthController::class, 'showVerifyLoginOtp'])->name('login.otp.verify');
    Route::post('/login/verify-otp', [AuthController::class, 'verifyLoginOtp']);
    Route::post('/login/resend-otp', [AuthController::class, 'resendLoginOtp'])->name('login.otp.resend');

    // Forgot Password via WhatsApp OTP
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
    Route::post('/forgot-password', [AuthController::class, 'processForgotPassword']);
    Route::get('/forgot-password/verify-otp', [AuthController::class, 'showVerifyResetOtp'])->name('password.reset.otp.verify');
    Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyResetOtp']);
    Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendResetOtp'])->name('password.reset.otp.resend');
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset.form');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Google Socialite OAuth
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Member & User Routes
Route::middleware(['auth'])->group(function () {
    // Checkout & Payment
    Route::get('/booking/checkout', [BookingController::class, 'checkoutPage'])->name('booking.checkout');
    Route::get('/booking/check-voucher', [BookingController::class, 'checkVoucher'])->name('booking.check-voucher');
    Route::post('/booking/reserve', [BookingController::class, 'reserve'])->name('booking.reserve');
    Route::get('/booking/pay/{id}', [BookingController::class, 'paymentPage'])->name('booking.pay');
    
    // Gateway endpoints
    Route::post('/booking/pay-wallet', [PaymentController::class, 'payWithWallet'])->name('booking.pay-wallet');
    Route::post('/booking/pay-doku', [PaymentController::class, 'payWithDoku'])->name('booking.pay-doku');

    Route::post('/booking/pay/{id}/upload-receipt', [PaymentController::class, 'uploadReceipt'])->name('booking.upload-receipt');

    // Customer / Member Dashboard
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
        Route::post('/profile/update', [DashboardController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/password', [DashboardController::class, 'updatePassword'])->name('profile.password');

        Route::get('/bookings', [DashboardController::class, 'bookings'])->name('bookings');
        Route::get('/bookings/{id}/ticket', [DashboardController::class, 'ticket'])->name('bookings.ticket');
        Route::post('/bookings/{id}/review', [DashboardController::class, 'submitReview'])->name('bookings.review');

        // Wallet
        Route::get('/wallet', [DashboardController::class, 'wallet'])->name('wallet');
        Route::post('/wallet/topup', [DashboardController::class, 'topUp'])->name('wallet.topup');

        // Loyalty points
        Route::get('/loyalty', [DashboardController::class, 'loyalty'])->name('loyalty');
        Route::post('/loyalty/redeem', [DashboardController::class, 'redeemVoucher'])->name('loyalty.redeem');
    });
});

// Admin & Operator Panel
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    
    // CRUD Courts
    Route::resource('courts', AdminController::class)->except(['show']);
    Route::post('/courts/{id}/upload-photos', [AdminController::class, 'uploadCourtPhotos'])->name('courts.photos');

    // Blocked Slots (Maintenance Scheduling)
    Route::get('/blocked-slots', [AdminController::class, 'blockedSlotsIndex'])->name('blocked-slots');
    Route::post('/blocked-slots', [AdminController::class, 'storeBlockedSlot'])->name('blocked-slots.store');
    Route::delete('/blocked-slots/{id}', [AdminController::class, 'deleteBlockedSlot'])->name('blocked-slots.destroy');

    // Manage Bookings
    Route::get('/bookings', [AdminController::class, 'bookingsIndex'])->name('bookings');
    Route::post('/bookings/{id}/status', [AdminController::class, 'updateBookingStatus'])->name('bookings.status');
    Route::post('/bookings/{id}/approve', [AdminController::class, 'approvePayment'])->name('bookings.approve');
    Route::post('/bookings/{id}/reject', [AdminController::class, 'rejectPayment'])->name('bookings.reject');
    Route::post('/bookings/{id}/refund', [AdminController::class, 'manualRefund'])->name('bookings.refund');

    // Manage Vouchers
    Route::get('/vouchers', [AdminController::class, 'vouchersIndex'])->name('vouchers');
    Route::post('/vouchers', [AdminController::class, 'storeVoucher'])->name('vouchers.store');
    Route::delete('/vouchers/{id}', [AdminController::class, 'deleteVoucher'])->name('vouchers.destroy');

    // Reports (PDF/Excel exports)
    Route::get('/reports', [AdminController::class, 'reportsIndex'])->name('reports');
    Route::get('/reports/export-pdf', [AdminController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('/reports/export-excel', [AdminController::class, 'exportExcel'])->name('reports.excel');

    // Spatie Activity Log List
    Route::get('/logs', [AdminController::class, 'activityLogs'])->name('logs');
});

// Payment Webhooks (Outside auth middleware, handle their own security)
Route::post('/booking/pay-doku/callback', [PaymentController::class, 'dokuCallback'])->name('booking.callback-doku');
