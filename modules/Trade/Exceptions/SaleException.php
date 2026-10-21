<?php

namespace Modules\Trade\Exceptions;

use App\Exceptions\BusinessException;

class SaleException extends BusinessException
{
    public const NOTHING_TO_RETURN = 'nothing_to_return';
    public const PRICE_NOT_SET = 'price_not_set';
    public const PRODUCT_NOT_FOUND = 'product_not_found';
    public const REFUND_AMOUNT_EXCEEDS = 'refund_amount_exceeds';
    public const RETURN_QUANTITY_EXCEEDS = 'return_quantity_exceeds';
    public const WRONG_PAYMENTS_AMOUNT = 'wrong_payments_amount';

    protected function messages(): array
    {
        return [
            self::NOTHING_TO_RETURN => 'К возврату не были выбраны, ни продукты, ни оплаты',
            self::PRICE_NOT_SET => 'Для товара ":name" не задана цена продажи',
            self::PRODUCT_NOT_FOUND => 'Товар ID :id не найден в продаже',
            self::REFUND_AMOUNT_EXCEEDS => 'Сумма возврата не может быть больше :available',
            self::RETURN_QUANTITY_EXCEEDS => 'Количество возврата товара ":name" не может быть больше :available',
            self::WRONG_PAYMENTS_AMOUNT => 'Сумма оплат (:paid) не совпадает с суммой продажи (:total)',
        ];
    }
}
