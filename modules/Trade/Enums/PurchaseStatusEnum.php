<?php

namespace Modules\Trade\Enums;

use App\Base\EnumTrait;

enum PurchaseStatusEnum: string
{
    use EnumTrait;

    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public static function labels(): array
    {
        return [
            self::PENDING->value => __('Ожидает'),
            self::COMPLETED->value => __('Завершено'),
            self::CANCELLED->value => __('Отменено'),
        ];
    }
}
