<?php

namespace Http\Admin\Company\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;

class CompanyFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        $query->when(
            user()->getService()->isSuperadmin() && !in_array(request()->route()->getActionMethod(), ['index', 'show']),
            function ($q) {
                $q->with(['credential']);
            },
        );

        return $query->filterByAdministeredCompanies('id');
    }
}
