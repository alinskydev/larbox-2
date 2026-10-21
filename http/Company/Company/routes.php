<?php

use Http\Company\Company\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

Route::prefix('company')
    ->group(function () {
        Route::apiResource('company', CompanyController::class)->only(['index', 'show', 'update']);
    });
