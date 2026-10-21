<?php

use Http\Common\System\Controllers\SystemController;
use Http\Common\System\Controllers\UiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->get('system', SystemController::class)->name('system');

Route::middleware(['auth:sanctum'])
    ->get('system/ui', UiController::class)->name('system.ui');
