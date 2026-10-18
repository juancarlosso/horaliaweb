<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppAuthenticationController;

Route::prefix('app')->name('api.app.')->group(function () {
    Route::post('/login', [AppAuthenticationController::class, 'login'])
        ->middleware('throttle:app-login')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AppAuthenticationController::class, 'me'])->name('me');
        Route::post('/logout', [AppAuthenticationController::class, 'logout'])->name('logout');
    });
});
