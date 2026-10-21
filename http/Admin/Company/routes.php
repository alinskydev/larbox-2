<?php

use Http\Admin\Company\Controllers\CompanyController;
use Http\Admin\Company\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;
use Modules\User\Middleware\UserSuperadminMiddleware;

Route::prefix('company')
    ->group(function () {
        Route::prefix('company')
            ->group(function () {
                Route::patch('{model}/reset-daily-limit', [CompanyController::class, 'resetDailyLimit'])
                    ->name('reset-daily-limit');

                Route::middleware(UserSuperadminMiddleware::class)
                    ->patch('{model}/update-credential', [CompanyController::class, 'updateCredential'])
                    ->name('update-credential');

                Route::apiResource('', CompanyController::class);
            });

        Route::apiResource('subscription', SubscriptionController::class)->except(['restore']);
    });
