<?php

namespace Http\Company\User\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;
use Modules\User\Enums\UserTypeEnum;

class RoleFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query
            ->filterByCurrentCompany()
            ->where('type', UserTypeEnum::COMPANY_EMPLOYEE->value);
    }
}
