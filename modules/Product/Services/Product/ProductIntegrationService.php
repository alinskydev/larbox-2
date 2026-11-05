<?php

namespace Modules\Product\Services\Product;

use App\Base\ModelService;
use App\Integration\IntegrationJob;
use Integrations\Store\Services\StoreProductService;
use Modules\Product\Models\Product;

/**
 * @property Product $model
 */
class ProductIntegrationService extends ModelService
{
    public function create(): void
    {
        (new StoreProductService())->ensureDailyLimit();

        dispatch(new IntegrationJob(
            service: StoreProductService::class,
            method: 'create',
            params: [
                'data' => [
                    'id' => $this->model->id,
                    'name' => $this->model->name,
                ],
            ],
            additionalData: [
                'brand_id' => $this->model->brand_id,
            ],
        ));
    }

    public function update(): void
    {
        dispatch(new IntegrationJob(
            service: StoreProductService::class,
            method: 'update',
            params: [
                'id' => $this->model->id,
                'data' => $this->model->toArray(),
            ],
        ));
    }

    public function delete(): void
    {
        dispatch(new IntegrationJob(
            service: StoreProductService::class,
            method: 'delete',
            params: [
                'id' => $this->model->id,
            ],
        ));
    }
}
