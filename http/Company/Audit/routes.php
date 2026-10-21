<?php

use Http\Company\Audit\Controllers\RecordActionController;
use Illuminate\Support\Facades\Route;

Route::prefix('audit')
    ->group(function () {
        Route::apiResource('record-action', RecordActionController::class)->only(['index', 'show']);
    });
