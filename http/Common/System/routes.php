<?php

use Http\Common\System\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->get('system', SystemController::class)->name('system');
