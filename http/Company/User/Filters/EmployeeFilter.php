<?php

namespace Http\Company\User\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;
use Modules\User\Enums\UserTypeEnum;

class EmployeeFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query
            ->filterByNestedCompanies()
            ->where('type', UserTypeEnum::COMPANY_EMPLOYEE->value);
    }
}
