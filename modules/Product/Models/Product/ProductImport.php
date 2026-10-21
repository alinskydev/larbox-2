<?php

namespace Modules\Product\Models\Product;

use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use App\Observers\SluggableObserver;
use Modules\Product\Models\Product;
use Modules\Product\Observers\ProductObserver;

class ProductImport extends Product
{
    protected static function bootRecordActionModelTrait(): void {}

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            SluggableObserver::class,
            ProductObserver::class,
        ]);
    }
}
