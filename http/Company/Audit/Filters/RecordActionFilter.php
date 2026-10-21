<?php

namespace Http\Company\Audit\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;

class RecordActionFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query->filterByNestedCompanies();
    }
}
