<?php

namespace Modules\Trade\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class PurchaseProductSearch extends Search
{
    public array $relations = [
        'company',
        'purchase',
        'product',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'purchase_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'product_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'purchase_price' => SearchFilterTypeEnum::BETWEEN_NUMBER,
        'sale_price' => SearchFilterTypeEnum::BETWEEN_NUMBER,
        'quantity' => SearchFilterTypeEnum::BETWEEN_NUMBER,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
