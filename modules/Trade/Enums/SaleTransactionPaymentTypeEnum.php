<?php

namespace Modules\Trade\Enums;

use App\Base\EnumTrait;

enum SaleTransactionPaymentTypeEnum: string
{
    use EnumTrait;

    case CASH = 'cash';
    case CARD = 'card';

    public static function labels(): array
    {
        return [
            self::CASH->value => __('Наличные'),
            self::CARD->value => __('Карта'),
        ];
    }
}
