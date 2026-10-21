<?php

namespace App\Base;

use Illuminate\Contracts\Validation\ValidationRule;

abstract class Rule implements ValidationRule
{
    protected string $errorMessage = 'An error occurred';

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!$this->passes($attribute, $value)) {
            $fail($this->errorMessage);
        }
    }

    abstract public function passes(string $attribute, mixed $value): bool;
}
