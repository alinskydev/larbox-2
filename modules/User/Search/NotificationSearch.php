<?php

namespace Modules\User\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class NotificationSearch extends Search
{
    public int $pageSize = 10;

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'type' => SearchFilterTypeEnum::EQUAL_RAW,
        'is_seen' => SearchFilterTypeEnum::EQUAL_RAW,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
