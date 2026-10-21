<?php

namespace Modules\Trade\Exceptions;

use App\Exceptions\BusinessException;

class PurchaseException extends BusinessException
{
    public const NO_PRODUCTS = 'no_products';
    public const NOT_PENDING = 'not_pending';

    protected function messages(): array
    {
        return [
            self::NO_PRODUCTS => 'Нельзя завершить оприходование без товаров',
            self::NOT_PENDING => 'Изменять можно только оприходование в статусе ":status"',
        ];
    }
}
