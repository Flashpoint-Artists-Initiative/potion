<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::middleware(['guest:api'])->group(function () {
        Route::post('/login', 'loginAction')->name('api.login');
        // Route::post('/register', 'registerAction')->name('register');
        // Route::post('/forgot-password', 'forgotPasswordAction')->name('password.email');
        // Route::post('/reset-password', 'resetPasswordAction')->name('password.update');
    });

    Route::post('/logout', 'logoutAction')->middleware(['auth'])->name('api.logout');

    // Route::middleware(['auth', 'token.refresh'])->prefix('email')->group(function () {
    //     Route::get('/verify/{id}/{hash}', 'verifyEmailAction')->middleware(['signed'])->name('verification.verify');
    //     Route::post('/resend-verification', 'resendVerificationEmailAction')->middleware(['throttle:6,1'])->name('verification.send');
    // });
});
