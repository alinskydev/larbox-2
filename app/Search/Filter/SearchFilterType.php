<?php

namespace App\Search\Filter;

use App\Base\EloquentBuilder;
use App\Search\Enums\SearchFilterConditionEnum;
use Illuminate\Database\Eloquent\Builder;

abstract class SearchFilterType
{
    protected string $condition;

    /**
     * @param EloquentBuilder $query
     */
    public function __construct(
        protected Builder $query,
        SearchFilterConditionEnum $condition,
        protected string $field,
        protected mixed $value,
    ) {
        $this->condition = $condition->value;

        $table = $this->query->getQuery()->from;

        if (!str_contains($table, ' as ')) {
            $this->field = "$table.$this->field";
        }
    }

    abstract public function process(): void;
}
