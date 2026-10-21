<?php

namespace Modules\Company\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;

class CompanySearch extends Search
{
    public array $relations = [
        'parent',
        'creator.profile',
        'subscription',
        'director.profile',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'parent_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'subscription_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'name' => SearchFilterTypeEnum::LIKE,
        'created_at' => SearchFilterTypeEnum::BETWEEN_DATE,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];

    public function filter(array $params): static
    {
        parent::filter($params);

        if (isset($params['is_root'])) {
            $this->query->when(
                (bool)$params['is_root'],
                fn($query) => $query->whereNull('parent_id'),
                fn($query) => $query->whereNotNull('parent_id'),
            );
        }

        return $this;
    }
}
