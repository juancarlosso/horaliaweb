<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppAuthenticationController;
use App\Http\Controllers\Api\AppAttendanceController;

Route::prefix('app')->name('api.app.')->group(function () {
    Route::post('/login', [AppAuthenticationController::class, 'login'])
        ->middleware('throttle:app-login')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AppAuthenticationController::class, 'me'])->name('me');
        Route::post('/logout', [AppAuthenticationController::class, 'logout'])->name('logout');
        Route::post('/attendance/verify-pin', [AppAttendanceController::class, 'verifyPin'])
            ->middleware('throttle:app-attendance-pin')->name('attendance.verify-pin');
        Route::post('/attendance/mark', [AppAttendanceController::class, 'mark'])
            ->middleware('throttle:app-attendance-pin')->name('attendance.mark');
        Route::post('/pauses/verify-pin', [AppAttendanceController::class, 'verifyPausePin'])
            ->middleware('throttle:app-attendance-pin')->name('pauses.verify-pin');
        Route::post('/pauses/start', [AppAttendanceController::class, 'startPause'])
            ->middleware('throttle:app-attendance-pin')->name('pauses.start');
        Route::post('/pauses/finish', [AppAttendanceController::class, 'finishPause'])
            ->middleware('throttle:app-attendance-pin')->name('pauses.finish');
    });
});
