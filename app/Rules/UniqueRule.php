<?php

namespace App\Rules;

use App\Base\EloquentBuilder;
use App\Base\Model;
use App\Base\Rule;
use App\Helpers\ClassHelper;
use Illuminate\Database\Eloquent\SoftDeletes;

class UniqueRule extends Rule
{
    /**
     * @param  ?\Closure(EloquentBuilder)  $additionalQuery
     */
    public function __construct(
        private Model $model,
        private bool $isFieldLocalized,
        private ?\Closure $additionalQuery = null,
        private bool $shouldShowDetailedErrorMessage = true,
    ) {}

    public function passes(string $attribute, mixed $value): bool
    {
        if (gettype($value) !== 'string') return false;

        $attributeParts = explode('.', $attribute);

        if ($this->isFieldLocalized) {
            $column = $attributeParts[count($attributeParts) - 2];
            $locale = end($attributeParts);

            $field = "$column->>'$locale'";
        } else {
            $field = end($attributeParts);
        }

        $query = $this->model->query()
            ->when(
                filter_var($value, FILTER_VALIDATE_INT) !== false,
                function ($q) use ($field, $value) {
                    $q->where($field, $value);
                },
                function ($q) use ($field, $value) {
                    $q->whereLower($field, $value);
                },
            )
            ->whereKeyNot($this->model->getKey());

        if (ClassHelper::uses($this->model, SoftDeletes::class)) {
            $query->withTrashed();
        }

        if ($this->additionalQuery) {
            ($this->additionalQuery)($query);
        }

        $duplicateModel = $query->first();

        if (!$duplicateModel) return true;

        if ($this->shouldShowDetailedErrorMessage) {
            $this->errorMessage = __('Данное значение поля уже используется в записи ":pk"', [
                'pk' => $duplicateModel->getKey(),
            ]);
        } else {
            $this->errorMessage = __('validation.unique');
        }

        return false;
    }
}
