<?php

namespace App\Base;

use App\Base\QueryBuilder\QueryBuilderRelationsTrait;
use App\Helpers\StringHelper;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

class QueryBuilder extends Builder
{
    use QueryBuilderRelationsTrait;

    protected Model $model;

    protected array $relations = [];
    protected array $additionalRelations = [];

    public function setParams(Model $model, array $relations, array $additionalRelations = []): static
    {
        $this->model = $model;
        $this->relations = $relations;
        $this->additionalRelations = $additionalRelations;

        return $this;
    }

    public function whereInThroughAny(string $column, array $values): static
    {
        if ($this->limit || $this->offset) {
            throw new \Exception('Limit and offset are not supported in relations');
        }

        $column = $this->getGrammar()->wrap($column);

        $values = array_filter($values, fn($value) => $value !== null);
        $values = array_map(fn($value) => '"' . addcslashes($value, '"\\') . '"', $values);

        return $this->whereRaw("$column = any(?)", ['{' . implode(',', $values) . '}']);
    }

    public function whereIlike(string|Expression $column, string $value, string $boolean = 'and'): static
    {
        $value = StringHelper::escapeForLikeCondition($value);
        return $this->where($column, 'ilike', "%$value%", $boolean);
    }

    public function orWhereIlike(string|Expression $column, string $value): static
    {
        return $this->whereIlike($column, $value, 'or');
    }

    public function get($columns = ['*'])
    {
        $records = parent::get($columns);

        if (!$this->relations || $records->isEmpty()) {
            return $records;
        }

        $records = $records->toArray();

        $this->relations = Arr::undot($this->relations);

        foreach ($this->relations as $name => $callback) {
            $relationName = strstr($name, '.', true) ?: $name;

            /** @var Relation */
            $relation = $this->model->$relationName();
            $model = $relation->getQuery()->getModel();

            $handler = $this->additionalRelations[$relation::class] ?? [$this, 'processRelation' . class_basename($relation)];

            if (!is_callable($handler)) {
                throw new \Exception($relation::class . ' not implemented');
            }

            $handler(
                records: $records,
                relation: $relation,
                name: $name,
                model: $model,
                callback: $callback,
            );
        }

        return collect($records);
    }
}
