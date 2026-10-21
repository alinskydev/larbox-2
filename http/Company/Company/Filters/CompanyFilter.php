<?php

namespace Http\Company\Company\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;

class CompanyFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query->filterByNestedCompanies('id');
    }
}
