<?php

namespace Modules\Trade\Observers;

use Modules\Trade\Enums\PurchaseStatusEnum;
use Modules\Trade\Models\Purchase;

class PurchaseObserver
{
    public function creating(Purchase $model): void
    {
        $model->status ??= PurchaseStatusEnum::PENDING->value;
    }

    public function updating(Purchase $model): void
    {
        $model->getService()->lockAndEnsurePending();

        if ($model->isDirty('status') && $model->status === PurchaseStatusEnum::COMPLETED->value) {
            $model->getService()->createBatch();
            $model->getService()->updateProductPrices();
        }
    }
}
