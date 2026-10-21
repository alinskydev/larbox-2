<?php

namespace Modules\System\Enums;

use App\Base\EnumTrait;

enum DailyLimitEntityTypeEnum: string
{
    use EnumTrait;

    case COMPANY = 'company';

    public static function labels(): array
    {
        return [
            self::COMPANY->value => __('Компания'),
        ];
    }
}
