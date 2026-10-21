<?php

use App\Middleware\ConcurrentWritesPreventionMiddleware;
use App\Middleware\IdempotencyMiddleware;
use App\Middleware\LocaleMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\User\Middleware\RoleMiddleware;

Route::middleware([
    LocaleMiddleware::class,
    'api',
    'throttle:api',
    ConcurrentWritesPreventionMiddleware::class,
])->group(function () {
    Route::prefix('admin')
        ->middleware([
            'auth:sanctum',
            RoleMiddleware::class,
            IdempotencyMiddleware::class,
        ])
        ->group(glob(base_path('http/Admin/*/routes.php')));

    Route::prefix('common')
        ->group(glob(base_path('http/Common/*/routes.php')));

    Route::prefix('company')
        ->middleware([
            'auth:sanctum',
            RoleMiddleware::class,
            IdempotencyMiddleware::class,
        ])
        ->group(glob(base_path('http/Company/*/routes.php')));

    if (!app()->isProduction()) {
        Route::prefix('emulator')
            ->group(glob(base_path('http/Emulator/*/routes.php')));
    }
});
