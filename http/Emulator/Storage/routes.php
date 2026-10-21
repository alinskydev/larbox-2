<?php

use Http\Emulator\Storage\Controllers\StorageController;
use Illuminate\Support\Facades\Route;

Route::prefix('storage')
    ->group(function () {
        Route::post('api/get/images', [StorageController::class, 'getImages'])->name('get-images');
        Route::post('api/upload', [StorageController::class, 'upload'])->name('upload');
    });
