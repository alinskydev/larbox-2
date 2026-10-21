<?php

namespace Http\Company\Product\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;

class ProductFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query->when(
            !in_array(request()->route()->getActionMethod(), ['index', 'show']),
            function ($q) {
                $q->filterByNestedCompanies();
            },
        );
    }
}
