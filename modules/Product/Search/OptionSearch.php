<?php

namespace Modules\Product\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class OptionSearch extends Search
{
    public array $relations = [
        'creator.profile',
        'company',
        'specification',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'specification_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'name' => SearchFilterTypeEnum::LOCALIZED_LIKE,
        'created_at' => SearchFilterTypeEnum::BETWEEN_DATE,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
