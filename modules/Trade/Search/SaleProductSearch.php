<?php

namespace Modules\Trade\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class SaleProductSearch extends Search
{
    public array $relations = [
        'company',
        'sale',
        'batch',
        'product',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'sale_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'batch_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'product_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'purchase_price' => SearchFilterTypeEnum::BETWEEN_NUMBER,
        'sale_price' => SearchFilterTypeEnum::BETWEEN_NUMBER,
        'sold_quantity' => SearchFilterTypeEnum::BETWEEN_NUMBER,
        'returned_quantity' => SearchFilterTypeEnum::BETWEEN_NUMBER,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
