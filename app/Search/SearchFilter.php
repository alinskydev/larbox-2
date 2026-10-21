<?php

namespace App\Search;

use App\Base\EloquentBuilder;
use App\Search\Enums\SearchFilterConditionEnum;
use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Filter\SearchFilterType;
use Illuminate\Database\Eloquent\Builder;

class SearchFilter
{
    /**
     * @param EloquentBuilder $query
     */
    public function __construct(
        private Builder $query,
        private array $filters,
        private array $params,
    ) {}

    public function process(): void
    {
        foreach ($this->params as $param => $value) {
            $filter = $this->filters[$param] ?? null;

            if ($filter === null) continue;

            if ($filter instanceof SearchFilterTypeEnum) {
                $this->applyFilter($this->query, $filter, SearchFilterConditionEnum::WHERE, $param, $value);
            } else {
                $this->processGroup($filter, $value);
            }
        }
    }

    private function processGroup(array $filterGroup, mixed $value): void
    {
        $this->query->where(function (Builder $query) use ($filterGroup, $value) {
            foreach ($filterGroup['filters'] as $param => $filterType) {
                $this->applyFilter($query, $filterType, $filterGroup['condition'], $param, $value);
            }
        });
    }

    private function applyFilter(
        Builder $query,
        SearchFilterTypeEnum $filterType,
        SearchFilterConditionEnum $condition,
        string $param,
        mixed $value,
    ): void {
        $paramParts = explode('.', $param);
        $field = array_pop($paramParts);

        if ($paramParts === []) {
            $this->createFilter($query, $filterType, $condition, $field, $value);
            return;
        }

        $query->{$condition->value . 'Has'}(
            implode('.', $paramParts),
            fn(Builder $query) => $this->createFilter($query, $filterType, SearchFilterConditionEnum::WHERE, $field, $value),
        );
    }

    private function createFilter(
        Builder $query,
        SearchFilterTypeEnum $filterType,
        SearchFilterConditionEnum $condition,
        string $field,
        mixed $value,
    ): void {
        /** @var SearchFilterType */
        $filter = new ($filterType->value)($query, $condition, $field, $value);
        $filter->process();
    }
}
