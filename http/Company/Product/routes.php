<?php

use Http\Company\Product\Controllers\BrandController;
use Http\Company\Product\Controllers\CategoryController;
use Http\Company\Product\Controllers\OptionController;
use Http\Company\Product\Controllers\ProductController;
use Http\Company\Product\Controllers\SpecificationController;
use Illuminate\Support\Facades\Route;
use Modules\User\Helpers\Role\RolePermissionHelper;

Route::prefix('product')
    ->group(function () {
        Route::prefix('brand')
            ->group(function () {
                RolePermissionHelper::registerAllRecordsRoute();

                Route::patch('{model}/set-name/{value}', [BrandController::class, 'setName'])->name('set-name');

                Route::apiResource('', BrandController::class);
            });

        Route::prefix('category')
            ->group(function () {
                Route::get('tree', [CategoryController::class, 'tree'])->name('tree');
                Route::patch('move', [CategoryController::class, 'move'])->name('move');

                Route::apiResource('', CategoryController::class);
            });

        Route::apiResource('option', OptionController::class)->except('restore');

        Route::prefix('product')
            ->group(function () {
                Route::prefix('excel-import')
                    ->group(function () {
                        Route::get('example', [ProductController::class, 'excelImportExample'])->name('example');
                        Route::post('process', [ProductController::class, 'excelImportProcess'])->name('process');
                    });

                Route::apiResource('', ProductController::class);
            });

        Route::apiResource('specification', SpecificationController::class);
    });
