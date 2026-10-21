<?php

namespace Modules\Product\Observers;

use Modules\Product\Exceptions\OptionException;
use Modules\Product\Models\Option;
use Modules\Product\Models\Product;

class OptionObserver
{
    public function deleting(Option $model): void
    {
        $locale = app()->getLocale();

        $productNames = Product::query()
            ->whereHas('options', function ($query) use ($model) {
                $query->where('id', $model->id);
            })
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get()
            ->pluck('name');

        if (!$productNames->isEmpty()) {
            throw new OptionException(OptionException::ATTACHED_TO_PRODUCTS, [
                'option' => $model->name[$locale],
                'products' => $productNames->join(', '),
            ]);
        }
    }
}
