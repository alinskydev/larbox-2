<?php

namespace Modules\Trade\Observers;

use Modules\Trade\Enums\SaleStatusEnum;
use Modules\Trade\Models\Sale;

class SaleObserver
{
    public function saved(Sale $model): void
    {
        if ($model->isDirty('status') && $model->status === SaleStatusEnum::COMPLETED->value) {
            $model->getService()->addProducts($model->saleProducts);
            $model->getService()->addPayments($model->salePayments);
        }
    }
}
