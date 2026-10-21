<?php

namespace App\Base;

use App\Base\EloquentBuilder\EloquentBuilderFilterByPermissionsTrait;
use App\Base\EloquentBuilder\EloquentBuilderFilterByUserTrait;
use App\Base\Model\ModelSoftDeleteTrait;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Model
 * @extends Builder<TModel>
 *
 * @mixin ModelSoftDeleteTrait
 * @mixin QueryBuilder
 *
 * @property QueryBuilder $query
 *
 * @method QueryBuilder getQuery()
 */
class EloquentBuilder extends Builder
{
    use EloquentBuilderFilterByUserTrait;
    use EloquentBuilderFilterByPermissionsTrait;

    public function whereLower(string $field, string $value): static
    {
        $field = str_replace(["'", '->>'], ['', '->'], $field);
        $field = $this->getQuery()->getGrammar()->wrap($field);

        return $this->whereRaw("LOWER({$field}) = LOWER(?)", [$value]);
    }

    public function getQueryWithRelations(): QueryBuilder
    {
        return $this->query->setParams(
            model: $this->model,
            relations: $this->eagerLoad,
        );
    }

    public function cloneForSummary(): static
    {
        $query = clone $this;
        $query->getQuery()->orders = [];
        $query->getQuery()->limit = null;
        $query->getQuery()->offset = null;

        return $query;
    }
}
