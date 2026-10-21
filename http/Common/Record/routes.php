<?php

use Http\Common\Record\Controllers\CreateController;
use Http\Common\Record\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('record')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('{type}', IndexController::class)->name('index');
        Route::post('{type}', CreateController::class)
            ->middleware('throttle:3')
            ->name('create');
    });
