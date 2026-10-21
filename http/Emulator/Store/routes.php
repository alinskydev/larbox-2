<?php

use Http\Emulator\Store\Controllers\AuthController;
use Http\Emulator\Store\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('store')
    ->group(function () {
        Route::post('auth/login', [AuthController::class, 'login'])->name('login');
        Route::prefix('product')
            ->group(function () {
                Route::get('list', [ProductController::class, 'list'])->name('list');
                Route::post('create', [ProductController::class, 'create'])->name('create');
                Route::post('update/{id}', [ProductController::class, 'update'])->name('update');
                Route::post('delete/{id}', [ProductController::class, 'delete'])->name('delete');
            });
    });
