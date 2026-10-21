<?php

namespace Http\Admin\User\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;
use Modules\User\Enums\UserTypeEnum;

class RoleFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query->whereIn('type', [
            UserTypeEnum::ADMIN->value,
            UserTypeEnum::COMPANY_DIRECTOR->value,
        ]);
    }
}
