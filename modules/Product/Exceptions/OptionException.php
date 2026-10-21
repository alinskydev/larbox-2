<?php

namespace Modules\Product\Exceptions;

use App\Exceptions\BusinessException;

class OptionException extends BusinessException
{
    public const ATTACHED_TO_PRODUCTS = 'attached_to_products';

    protected function messages(): array
    {
        return [
            self::ATTACHED_TO_PRODUCTS => 'Опция ":option" не подлежит удалению, так как она привязана к продуктам ":products"',
        ];
    }
}
