<?php

namespace Modules\Trade\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class RecalculationProductSearch extends Search
{
    public array $relations = [
        'creator.profile',
        'company',
        'product',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'product_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'price' => SearchFilterTypeEnum::BETWEEN_NUMBER,
        'created_at' => SearchFilterTypeEnum::BETWEEN_DATE,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
