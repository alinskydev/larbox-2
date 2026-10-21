<?php

namespace Http\Company\Product\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;
use Modules\User\Helpers\Role\RolePermissionHelper;

class BrandFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query
            ->filterByOwnRecords(RolePermissionHelper::ROUTE_COMPANY_PRODUCT_BRAND)
            ->when(
                !in_array(request()->route()->getActionMethod(), ['index', 'show']),
                function ($q) {
                    $q->filterByNestedCompanies();
                },
            );
    }
}
