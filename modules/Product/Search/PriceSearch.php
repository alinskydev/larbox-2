<?php

namespace Modules\Product\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class PriceSearch extends Search
{
    public array $relations = [
        'product',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'product_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'price' => SearchFilterTypeEnum::BETWEEN_NUMBER,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
