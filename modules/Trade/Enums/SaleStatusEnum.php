<?php

namespace Modules\Trade\Enums;

use App\Base\EnumTrait;

enum SaleStatusEnum: string
{
    use EnumTrait;

    case CREATED = 'created';
    case COMPLETED = 'completed';
    case PARTIALLY_CANCELLED = 'partially_cancelled';
    case FULLY_CANCELLED = 'fully_cancelled';

    public static function labels(): array
    {
        return [
            self::CREATED->value => __('Создано'),
            self::COMPLETED->value => __('Завершено'),
            self::PARTIALLY_CANCELLED->value => __('Частично отменено'),
            self::FULLY_CANCELLED->value => __('Полностью отменено'),
        ];
    }
}
