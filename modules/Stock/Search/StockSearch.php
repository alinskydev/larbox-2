<?php

namespace Modules\Stock\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;
use Illuminate\Support\Facades\DB;

class StockSearch extends Search
{
    public array $relations = [
        'creator.profile',
        'company',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'name' => SearchFilterTypeEnum::LIKE,
        'created_at' => SearchFilterTypeEnum::BETWEEN_DATE,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];

    public function show(array $params): static
    {
        parent::show($params);

        if (in_array('product_quantity', $params)) {
            $this->query->withAggregate(
                'products as product_quantity',
                DB::raw('coalesce(sum(stock_product.quantity), 0)'),
            );
        }

        return $this;
    }
}
