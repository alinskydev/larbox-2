<?php

namespace Modules\User\Enums;

use App\Base\EnumTrait;

enum NotificationTypeEnum: string
{
    use EnumTrait;

    case ANNOUNCEMENT = 'announcement';
    case MESSAGE = 'message';

    case EXCEL_EXPORT_COMPLETED = 'excel_export_completed';
    case EXCEL_EXPORT_FAILED = 'excel_export_failed';

    case EXCEL_IMPORT_COMPLETED = 'excel_import_completed';
    case EXCEL_IMPORT_FAILED = 'excel_import_failed';

    public static function labels(): array
    {
        $types = self::values();
        $types = array_combine($types, $types);

        return array_map(fn($type) => __("user_notifications.$type.label"), $types);
    }
}
