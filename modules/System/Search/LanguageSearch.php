<?php

namespace Modules\System\Search;

use App\Search\Enums\SearchFilterConditionEnum;
use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class LanguageSearch extends Search
{
    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'is_active' => SearchFilterTypeEnum::EQUAL_RAW,
        'is_main' => SearchFilterTypeEnum::EQUAL_RAW,

        'search' => [
            'condition' => SearchFilterConditionEnum::OR_WHERE,
            'filters' => [
                'name' => SearchFilterTypeEnum::LIKE,
                'code' => SearchFilterTypeEnum::EQUAL_STRING,
            ],
        ],
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
        'name' => SearchSortTypeEnum::RAW,
        'code' => SearchSortTypeEnum::RAW,
    ];
}
