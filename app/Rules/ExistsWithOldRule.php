<?php

namespace App\Rules;

use App\Base\EloquentBuilder;
use App\Base\Model;
use App\Base\Rule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ExistsWithOldRule extends Rule
{
    /**
     * @param EloquentBuilder $query
     */
    public function __construct(
        private Model $model,
        private Builder $query,
        private ?string $pk = null,
        ?string $errorMessage = null,
    ) {
        $this->pk ??= $this->query->getModel()->getKeyName();
        $this->errorMessage = $errorMessage ?? __('Выбранная запись не существует или была удалена');
    }

    public function passes(string $attribute, mixed $value): bool
    {
        $query = clone $this->query;

        $oldValues = data_get($this->model, $attribute);
        $oldValues = $oldValues instanceof Collection ? $oldValues->pluck($this->pk)->toArray() : (array)$oldValues;

        $newValues = array_diff((array)$value, $oldValues);

        if (!$newValues) return true;

        $query->whereIn($this->pk, $newValues);

        return $query->count() === count($newValues);
    }
}
