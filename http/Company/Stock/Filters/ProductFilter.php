<?php

namespace Http\Company\Stock\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;

class ProductFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query->filterByNestedCompanies();
    }
}
