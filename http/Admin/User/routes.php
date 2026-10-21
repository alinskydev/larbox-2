<?php

use Http\Admin\User\Controllers\NotificationController;
use Http\Admin\User\Controllers\RoleController;
use Http\Admin\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Modules\User\Middleware\UserSuperadminMiddleware;

Route::prefix('user')
    ->group(function () {
        Route::middleware(UserSuperadminMiddleware::class)
            ->group(function () {
                Route::apiResource('notification', NotificationController::class)->only(['create']);

                Route::prefix('role')
                    ->group(function () {
                        Route::get('available-routes', [RoleController::class, 'availableRoutes'])
                            ->name('available-routes');

                        Route::apiResource('', RoleController::class)->except(['restore']);
                    });

                Route::prefix('user')
                    ->group(function () {
                        Route::patch('{model}/auth-as', [UserController::class, 'authAs'])->name('auth-as');
                        Route::apiResource('', UserController::class);
                    });
            });
    });
