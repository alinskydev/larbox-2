<?php

use Http\Admin\System\Controllers\LanguageController;
use Http\Admin\System\Controllers\SettingController;
use Illuminate\Support\Facades\Route;
use Modules\User\Middleware\UserSuperadminMiddleware;

Route::prefix('system')
    ->middleware(UserSuperadminMiddleware::class)
    ->group(function () {
        Route::apiResource('language', LanguageController::class)->only(['index', 'show', 'update']);

        Route::prefix('setting')
            ->group(function () {
                Route::get('', [SettingController::class, 'index'])->name('index');
                Route::put('', [SettingController::class, 'update'])->name('update');
            });
    });
