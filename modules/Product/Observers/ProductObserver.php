<?php

namespace Modules\Product\Observers;

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
        (new StoreProductService())
            ->setAdditionalData([
                'brand_id' => $model->brand->id,
            ])
            ->create([
                'id' => $model->id,
                'name' => $model->name,
            ]);
    }

    public function updated(Product $model): void
    {
        (new StoreProductService())->update($model->id, $model->toArray());
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
        (new StoreProductService())->delete($model->id);
    }
}
