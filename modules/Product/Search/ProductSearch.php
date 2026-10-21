<?php

namespace Modules\Product\Search;

use App\Search\Enums\SearchFilterConditionEnum;
use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductSearch extends Search
{
    public array $relations = [
        'parent',
        'creator.profile',
        'company',
        'category',
        'category.ancestors',
        'brand',
        'options',
        'options.specification',
        'price',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'brand_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'price.price' => SearchFilterTypeEnum::BETWEEN_NUMBER,
        'created_at' => SearchFilterTypeEnum::BETWEEN_DATE,

        'category_id' => [
            'condition' => SearchFilterConditionEnum::OR_WHERE,
            'filters' => [
                'category_id' => SearchFilterTypeEnum::EQUAL_RAW,
                'category.ancestors.id' => SearchFilterTypeEnum::EQUAL_RAW,
            ],
        ],

        'search' => [
            'condition' => SearchFilterConditionEnum::OR_WHERE,
            'filters' => [
                'full_text' => SearchFilterTypeEnum::LIKE,
                'barcode' => SearchFilterTypeEnum::EQUAL_RAW,
            ],
        ],
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];

    public function filter(array $params): static
    {
        parent::filter($params);

        if ($value = Arr::get($params, 'parent_id')) {
            $this->query->when(
                $value === 'none',
                fn($query) => $query->whereNull('parent_id'),
                fn($query) => $query->where('parent_id', $value),
            );
        }

        return $this;
    }

    public function show(array $params): static
    {
        parent::show($params);

        if (in_array('stock_quantity', $params)) {
            $this->query->withAggregate(
                'stock_products as stock_quantity',
                DB::raw('coalesce(sum(stock_product.quantity), 0)'),
            );
        }

        return $this;
    }
}
