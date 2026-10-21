<?php

namespace Modules\Product\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;
use Modules\Product\Models\Product;

class CategorySearch extends Search
{
    public array $defaultSort = ['lft'];

    public array $relations = [
        'creator.profile',
        'company',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'parent_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'name' => SearchFilterTypeEnum::LOCALIZED_LIKE,
    ];

    public array $sorts = [
        'lft' => SearchSortTypeEnum::RAW,
    ];

    public function with(array $params): static
    {
        parent::with($params);

        $this->query->with(['ancestors']);

        return $this;
    }

    public function filter(array $params): static
    {
        parent::filter($params);

        $this->query->whereNotNull('parent_id');

        return $this;
    }

    public function show(array $params): static
    {
        parent::show($params);

        if (in_array('products_count', $params)) {
            $this->query->addSelect([
                'products_count' => Product::query()
                    ->selectRaw('count(*)')
                    ->join('product_category as p_c', 'p_c.id', 'category_id')
                    ->whereBetweenColumns('p_c.lft', ['product_category.lft', 'product_category.rgt'])
                    ->whereNull('p_c.deleted_at'),
            ]);
        }

        return $this;
    }
}
