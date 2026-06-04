<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Authentication Endpoints
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'apiLogin']);
    Route::post('/register', [AuthController::class, 'apiRegister']);
});

// Midtrans Payment Webhook Callback (CSRF Excluded)
Route::post('/payments/callback', [PaymentController::class, 'midtransCallback'])->name('api.payments.callback');

// Sanctum Protected & Rate-limited Endpoints (60 requests/minute)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Courts availability APIs
    Route::get('/courts', [CourtController::class, 'apiIndex']);
    Route::get('/courts/{id}/availability', [CourtController::class, 'apiAvailability']);

    // Bookings APIs
    Route::post('/bookings', [BookingController::class, 'apiReserve']);
    Route::get('/user/bookings', [BookingController::class, 'apiUserBookings']);
});
