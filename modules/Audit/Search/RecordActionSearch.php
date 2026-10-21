<?php

namespace Modules\Audit\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;
use Illuminate\Support\Arr;
use Modules\Product\Models\Product;
use Modules\Product\Models\Specification;
use Modules\User\Models\User;

class RecordActionSearch extends Search
{
    public array $relations = [
        'creator.profile',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'auditable_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'type' => SearchFilterTypeEnum::EQUAL_RAW,
        'created_at' => SearchFilterTypeEnum::BETWEEN_DATE,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];

    public function filter(array $params): static
    {
        parent::filter($params);

        if ($value = Arr::get($params, 'auditable_type')) {
            $this->query->where('auditable_type', match ($value) {
                'product' => Product::class,
                'product_specification' => Specification::class,
                'user' => User::class,
                default => null,
            });
        }

        return $this;
    }
}
