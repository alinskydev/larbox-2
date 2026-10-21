<?php

namespace Modules\Trade\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class SaleSearch extends Search
{
    public array $relations = [
        'creator.profile',
        'company',
        'stock',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'stock_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'status' => SearchFilterTypeEnum::EQUAL_RAW,
        'created_at' => SearchFilterTypeEnum::BETWEEN_DATE,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
