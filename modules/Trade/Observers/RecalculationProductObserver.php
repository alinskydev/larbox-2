<?php

namespace Modules\Trade\Observers;

use Modules\Trade\Models\RecalculationProduct;

class RecalculationProductObserver
{
    public function created(RecalculationProduct $model): void
    {
        $model->getService()->updateProductPrice();
    }
}
