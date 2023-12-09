<?php

use App\Http\Controllers\Fortify\AuthenticationController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\RoutePath;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['prefix' => 'v1'], function() {
    Route::get("/authtest", [AuthenticationController::class, 'authtest'])
        ->middleware(['auth', 'verified'])
        ->name('authtest');

    $verificationLimiter = config('fortify.limiters.verification', '6,1');

    // Override the email verification route from fortify so it doesn't require being logged in (Which wouldn't work with JWTs)
    // TODO: This might not be needed if the front-end is keeping the JWT in local storage
    Route::get(RoutePath::for('verification.verify', '/email/verify/{id}/{hash}'), [AuthenticationController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:'.$verificationLimiter])
        ->name('verification.verify');

    // This route doesn't work without session storage, but we can't easily remove it from fortify, so make it do nothing
    Route::get(RoutePath::for('password.confirmation', '/user/confirmed-password-status'), function(){
        return response()->json("");
    });

});