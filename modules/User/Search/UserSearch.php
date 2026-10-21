<?php

namespace Modules\User\Search;

use App\Search\Enums\SearchFilterConditionEnum;
use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;
use Illuminate\Support\Arr;
use Modules\User\Builders\UserBuilder;

/**
 * @property UserBuilder $query
 */
class UserSearch extends Search
{
    public array $relations = [
        'creator.profile',
        'profile',
        'roles',
        'company',
        'responsible_companies',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::IN,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'type' => SearchFilterTypeEnum::EQUAL_RAW,

        'responsible_companies.id' => SearchFilterTypeEnum::IN,
        'roles.id' => SearchFilterTypeEnum::IN,

        'username_or_phone' => [
            'condition' => SearchFilterConditionEnum::OR_WHERE,
            'filters' => [
                'username' => SearchFilterTypeEnum::LIKE,
                'phone' => SearchFilterTypeEnum::LIKE,
            ],
        ],
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
        'username' => SearchSortTypeEnum::RAW,
    ];

    public function filter(array $params): static
    {
        parent::filter($params);

        if ($value = Arr::get($params, 'full_name')) {
            $this->query->whereFullNameLike($value);
        }

        return $this;
    }
}
