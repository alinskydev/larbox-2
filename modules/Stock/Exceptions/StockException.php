<?php

namespace Modules\Stock\Exceptions;

use App\Exceptions\BusinessException;

class StockException extends BusinessException
{
    public const NOT_ENOUGH_PRODUCT = 'not_enough_product';

    protected function messages(): array
    {
        return [
            self::NOT_ENOUGH_PRODUCT => 'Недостаточно товара ":name" на складе',
        ];
    }
}
