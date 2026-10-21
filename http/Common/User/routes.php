<?php

use Http\Common\User\Controllers\NotificationController;
use Http\Common\User\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::prefix('notification')
            ->group(function () {
                Route::get('{model}/download', [NotificationController::class, 'download'])->name('download');
                Route::patch('mark-all-seen', [NotificationController::class, 'markAllSeen'])->name('mark-all-seen');

                Route::apiResource('', NotificationController::class)->only(['index', 'show']);
            });

        Route::prefix('profile')
            ->group(function () {
                Route::get('', [ProfileController::class, 'index'])->name('index');
                Route::put('', [ProfileController::class, 'update'])->name('update');
            });
    });
