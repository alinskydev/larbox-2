<?php

use Http\Company\User\Controllers\EmployeeController;
use Http\Company\User\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')
    ->group(function () {
        Route::apiResource('employee', EmployeeController::class);

        Route::prefix('role')
            ->group(function () {
                Route::get('available-routes', [RoleController::class, 'availableRoutes'])->name('available-routes');
                Route::apiResource('', RoleController::class)->except(['restore']);
            });
    });
