<?php

use Http\Company\Trade\Controllers\PurchaseController;
use Http\Company\Trade\Controllers\PurchaseProductController;
use Http\Company\Trade\Controllers\RecalculationProductController;
use Http\Company\Trade\Controllers\SaleController;
use Http\Company\Trade\Controllers\SaleProductController;
use Http\Company\Trade\Controllers\SaleTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('trade')
    ->group(function () {
        Route::prefix('purchase')
            ->group(function () {
                Route::post('{model}/add-products', [PurchaseController::class, 'addProducts'])->name('add-products');
                Route::patch('{model}/remove-products', [PurchaseController::class, 'removeProducts'])
                    ->name('remove-products');

                Route::patch('{model}/complete', [PurchaseController::class, 'complete'])->name('complete');
                Route::patch('{model}/cancel', [PurchaseController::class, 'cancel'])->name('cancel');

                Route::apiResource('', PurchaseController::class)->only(['index', 'show', 'create', 'update']);
            });

        Route::apiResource('purchase-product', PurchaseProductController::class)->only(['index', 'show']);
        Route::apiResource('recalculation-product', RecalculationProductController::class)->only(['index', 'show', 'create']);

        Route::prefix('sale')
            ->group(function () {
                Route::patch('{model}/return', [SaleController::class, 'return'])->name('return');

                Route::apiResource('', SaleController::class)->only(['index', 'show', 'create']);
            });

        Route::apiResource('sale-product', SaleProductController::class)->only(['index', 'show']);
        Route::apiResource('sale-transaction', SaleTransactionController::class)->only(['index', 'show']);
    });
