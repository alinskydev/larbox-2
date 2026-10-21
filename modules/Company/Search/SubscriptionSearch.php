<?php

namespace Modules\Company\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class SubscriptionSearch extends Search
{
    public array $relations = [
        'creator.profile',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'name' => SearchFilterTypeEnum::LIKE,
        'created_at' => SearchFilterTypeEnum::BETWEEN_DATE,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
