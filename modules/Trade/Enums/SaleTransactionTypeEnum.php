<?php

namespace Modules\Trade\Enums;

use App\Base\EnumTrait;

enum SaleTransactionTypeEnum: string
{
    use EnumTrait;

    case PAYMENT = 'payment';
    case REFUND = 'refund';

    public static function labels(): array
    {
        return [
            self::PAYMENT->value => __('Оплата'),
            self::REFUND->value => __('Возврат'),
        ];
    }
}
