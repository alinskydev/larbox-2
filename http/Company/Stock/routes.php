<?php

use Http\Company\Stock\Controllers\ProductController;
use Http\Company\Stock\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::prefix('stock')
    ->group(function () {
        Route::apiResource('stock', StockController::class);
        Route::apiResource('product', ProductController::class)->only(['index', 'show']);
    });
