<?php

namespace Modules\Product\Observers;

use App\Integration\IntegrationJob;
use Integrations\Store\Services\StoreProductService;
use Modules\Product\Models\Product;

class ProductObserver
{
    public function saving(Product $model): void
    {
        $model->full_name ??= $model->name;

        if ($model->parent_id && (!$model->category_id || !$model->brand_id)) {
            $model->category_id ??= $model->parent->category_id;
            $model->brand_id ??= $model->parent->brand_id;
        }
    }

    public function created(Product $model): void
    {
        (new StoreProductService())->ensureDailyLimit();

        dispatch(new IntegrationJob(
            service: StoreProductService::class,
            method: 'create',
            params: [
                'data' => [
                    'id' => $model->id,
                    'name' => $model->name,
                ],
            ],
            additionalData: [
                'brand_id' => $model->brand_id,
            ],
        ));
    }

    public function updated(Product $model): void
    {
        dispatch(new IntegrationJob(
            service: StoreProductService::class,
            method: 'update',
            params: [
                'id' => $model->id,
                'data' => $model->toArray(),
            ],
        ));
    }

    public function saved(Product $model): void
    {
        if ($model->wasChanged(['category_id', 'brand_id'])) {
            Product::query()
                ->where('parent_id', $model->id)
                ->getQuery()
                ->update([
                    'category_id' => $model->category_id,
                    'brand_id' => $model->brand_id,
                ]);
        }

        $locale = app()->getLocale();
        $fullName = implode(' | ', [$model->name, ...$model->options->pluck("name.$locale")]);

        if ($model->full_name !== $fullName) {
            $model->updateQuietly(['full_name' => $fullName]);
        }
    }

    public function deleted(Product $model): void
    {
        dispatch(new IntegrationJob(
            service: StoreProductService::class,
            method: 'delete',
            params: [
                'id' => $model->id,
            ],
        ));
    }
}
