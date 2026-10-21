<?php

namespace Modules\Stock\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class ProductSearch extends Search
{
    public array $relations = [
        'company',
        'stock',
        'batch',
        'product',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'stock_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'batch_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'product_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'purchase_price' => SearchFilterTypeEnum::BETWEEN_NUMBER,
        'quantity' => SearchFilterTypeEnum::BETWEEN_NUMBER,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
