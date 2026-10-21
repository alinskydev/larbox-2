<?php

use Http\Common\Auth\Controllers\AuthController;
use Http\Common\Auth\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->middleware('throttle:auth')
    ->group(function () {
        Route::post('login', [AuthController::class, 'login'])
            ->name('login');

        Route::post('logout', [AuthController::class, 'logout'])
            ->middleware(['auth:sanctum'])
            ->name('logout');

        Route::prefix('reset-password')
            ->group(function () {
                Route::post('send-code', [ResetPasswordController::class, 'sendCode'])
                    ->name('send-code');

                Route::post('complete', [ResetPasswordController::class, 'complete'])
                    ->name('complete');
            });
    });
