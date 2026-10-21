<?php

namespace Http\Company\Trade\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;

class SaleFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query->filterByNestedCompanies();
    }
}
