<?php

namespace Modules\Trade\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class SaleTransactionSearch extends Search
{
    public array $relations = [
        'company',
        'sale',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'sale_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'type' => SearchFilterTypeEnum::EQUAL_RAW,
        'payment_type' => SearchFilterTypeEnum::EQUAL_RAW,
        'amount' => SearchFilterTypeEnum::BETWEEN_NUMBER,
        'created_at' => SearchFilterTypeEnum::BETWEEN_DATE,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];
}
