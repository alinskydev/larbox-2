<?php

namespace App\Rules;

use App\Base\Rule;

class PhoneRule extends Rule
{
    public function passes(string $attribute, mixed $value): bool
    {
        $this->errorMessage = __('Формат поля должен быть вида: :format', [
            'format' => '998xxyyyzzzz',
        ]);

        return is_string($value) && preg_match('/^998[0-9]{9}$/', $value);
    }
}
