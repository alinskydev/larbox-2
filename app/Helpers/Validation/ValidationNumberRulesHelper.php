<?php

namespace App\Helpers\Validation;

class ValidationNumberRulesHelper
{
    public static function amount(
        bool $isRequired,
        float $min = 0.01,
        float $max = 999999999999.99,
    ): array {
        return [
            $isRequired ? 'required' : 'nullable',
            'decimal:0,2',
            "min:$min",
            "max:$max",
        ];
    }
}
