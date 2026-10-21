<?php

use Http\Admin\History\Controllers\DictionaryController;
use Http\Admin\History\Controllers\StoreHttpLogController;
use Illuminate\Support\Facades\Route;
use Modules\User\Middleware\UserSuperadminMiddleware;

Route::prefix('history')
    ->middleware(UserSuperadminMiddleware::class)
    ->group(function () {
        Route::get('dictionary', DictionaryController::class)->name('dictionary');

        Route::apiResource('store-http-log', StoreHttpLogController::class)->only(['index', 'show']);
    });
