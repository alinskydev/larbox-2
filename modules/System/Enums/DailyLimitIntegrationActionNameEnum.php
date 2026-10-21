<?php

namespace Modules\System\Enums;

use App\Base\EnumTrait;

enum DailyLimitIntegrationActionNameEnum: string
{
    use EnumTrait;

    case STORE_PRODUCT = 'store_product';

    public static function labels(): array
    {
        return [
            self::STORE_PRODUCT->value => __('Магазин (продукты)'),
        ];
    }

    public static function limit(self $integration): int
    {
        $limits = [
            self::STORE_PRODUCT->value => 1000,
        ];

        return $limits[$integration->value];
    }
}
