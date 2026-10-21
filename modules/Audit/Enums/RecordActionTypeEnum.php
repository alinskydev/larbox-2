<?php

namespace Modules\Audit\Enums;

use App\Base\EnumTrait;

enum RecordActionTypeEnum: string
{
    use EnumTrait;

    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case AUTH_AS = 'auth_as';

    public static function labels(): array
    {
        return [
            self::CREATE->value => __('Создание'),
            self::UPDATE->value => __('Редактирование'),
            self::DELETE->value => __('Удаление'),
            self::RESTORE->value => __('Восстановление'),
            self::AUTH_AS->value => __('Авторизация от лица пользователя'),
        ];
    }
}
