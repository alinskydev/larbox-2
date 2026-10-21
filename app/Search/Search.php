<?php

namespace App\Search;

use App\Base\EloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * @property EloquentBuilder $query
 */
class Search
{
    public Builder $query;

    public array $relations = [];
    public array $filters = [];
    public array $sorts = [];

    public array $defaultSort = ['-id'];

    public int $pageSize = 20;

    public function setQuery(Builder $query): static
    {
        $this->query = $query;
        return $this;
    }

    public function with(array $params): static
    {
        $params = Arr::flatten($params);
        $params = array_intersect($params, $this->relations);

        $this->query->with($params);

        return $this;
    }

    public function filter(array $params): static
    {
        $filter = new SearchFilter(
            query: $this->query,
            filters: $this->filters,
            params: $params,
        );

        $filter->process();

        return $this;
    }

    public function sort(array $params): static
    {
        $params = Arr::flatten($params) ?: $this->defaultSort;

        $sorter = new SearchSort(
            query: $this->query,
            sorts: $this->sorts,
            params: $params,
        );

        $sorter->process();

        return $this;
    }

    public function show(array $params): static
    {
        $params = Arr::flatten($params);

        $shower = new SearchShow(
            query: $this->query,
            params: $params,
        );

        $shower->process();

        return $this;
    }

    public function setPageSize(int $pageSize): static
    {
        if ($pageSize > 0 && $pageSize <= $this->pageSize) {
            $this->pageSize = $pageSize;
        }

        return $this;
    }
}
