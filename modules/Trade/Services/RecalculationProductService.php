<?php

namespace Modules\Trade\Services;

use App\Base\ModelService;
use Modules\Product\Models\Price;
use Modules\Trade\Models\RecalculationProduct;

/**
 * @property RecalculationProduct $model
 */
class RecalculationProductService extends ModelService
{
    public function updateProductPrice(): void
    {
        $row = [
            'company_id' => $this->model->company_id,
            'product_id' => $this->model->product_id,
            'price' => $this->model->price,
        ];

        Price::query()->upsert(
            values: [$row],
            uniqueBy: ['company_id', 'product_id'],
            update: ['price'],
        );
    }
}
