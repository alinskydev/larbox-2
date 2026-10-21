<?php

namespace Modules\History\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\History\Helpers\SearchHelper;

class StoreHttpLogSearch extends Search
{
    public const REQUEST_PATHS = [
        'product/list' => 'product_list',
        'product/create' => 'product_create',
        'product/update/*' => 'product_update',
        'product/delete/*' => 'product_delete',
    ];

    public int $pageSize = 10;

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'response_status' => SearchFilterTypeEnum::EQUAL_RAW,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];

    public function filter(array $params): static
    {
        parent::filter($params);

        $createdAtInterval = SearchHelper::createdAtInterval($params);

        $this->query
            ->where(function ($query) {
                $query
                    ->whereIn('request_path', array_keys(static::REQUEST_PATHS))
                    ->orWhere('request_path', 'ilike', 'product/update/%')
                    ->orWhere('request_path', 'ilike', 'product/delete/%');
            })
            ->whereBetween('created_at', $createdAtInterval);

        if ($value = Arr::get($params, 'request_path')) {
            $this->query->where('request_path', 'ilike', str_replace('*', '%', $value));
        }

        if ($value = Arr::get($params, 'product_create___name')) {
            $this->query->whereIlike(DB::raw("request_body->>'name'"), $value);
        }

        if ($value = Arr::get($params, 'product_create___brand_name')) {
            $this->query->whereIlike(DB::raw("additional->'brand'->>'name'"), $value);
        }

        return $this;
    }
}
