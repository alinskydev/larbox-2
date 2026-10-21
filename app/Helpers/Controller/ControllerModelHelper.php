<?php

namespace App\Helpers\Controller;

use App\Base\Model;

class ControllerModelHelper
{
    public static function setValues(
        Model $model,
        array $values,
        array $rules,
        array $condition = [],
        ?string $conditionErrorMessage = null,
    ): void {
        static::checkCondition($model, $condition, $conditionErrorMessage);
        $validated = validator($values, $rules)->validate();
        $model->safelySave($validated);
    }

    public static function toggleValue(
        Model $model,
        string $field,
        array $condition = [],
        ?string $conditionErrorMessage = null,
    ): void {
        static::checkCondition($model, $condition, $conditionErrorMessage);
        $model->safelySave([$field => !$model->{$field}]);
    }

    public static function delete(
        Model $model,
        array $condition = [],
        ?string $conditionErrorMessage = null,
    ): void {
        static::checkCondition($model, $condition, $conditionErrorMessage);
        $model->safelyDelete();
    }

    public static function checkCondition(
        Model $model,
        array $condition,
        ?string $conditionErrorMessage = null,
    ): void {
        $conditionErrorMessage ??= __('Для данной записи это действие запрещено');

        if ($condition && validator($model->getAttributes(), $condition)->fails()) {
            throw new \Exception($conditionErrorMessage);
        }
    }
}
