<?php

namespace Modules\User\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class RoleSearch extends Search
{
    public array $relations = [
        'creator.profile',
        'company',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'name' => SearchFilterTypeEnum::LIKE,
        'type' => SearchFilterTypeEnum::EQUAL_RAW,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
