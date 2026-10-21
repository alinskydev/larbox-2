<?php

namespace Modules\Product\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class BrandSearch extends Search
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

        if (in_array('products_count', $params)) {
            $this->query->withCount([
                'products' => function ($query) {
                    $query->withoutTrashed();
                },
            ]);
        }

        return $this;
    }
}
