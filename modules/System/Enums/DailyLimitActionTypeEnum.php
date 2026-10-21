<?php

namespace Modules\System\Enums;

use App\Base\EnumTrait;

enum DailyLimitActionTypeEnum: string
{
    use EnumTrait;

    case INTEGRATION = 'integration';
    case RECORD = 'record';

    public static function labels(): array
    {
        return [
            self::INTEGRATION->value => __('Интеграция'),
            self::RECORD->value => __('Запись'),
        ];
    }
}
