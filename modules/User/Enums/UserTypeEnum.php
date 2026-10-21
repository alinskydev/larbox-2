<?php

namespace Modules\User\Enums;

use App\Base\EnumTrait;

enum UserTypeEnum: string
{
    use EnumTrait;

    case ADMIN = 'admin';
    case COMPANY_DIRECTOR = 'company_director';
    case COMPANY_EMPLOYEE = 'company_employee';

    public static function labels(): array
    {
        return [
            self::ADMIN->value => __('Администратор'),
            self::COMPANY_DIRECTOR->value => __('Директор компании'),
            self::COMPANY_EMPLOYEE->value => __('Сотрудник компании'),
        ];
    }

    public static function isAdminType(string $type): bool
    {
        return $type === self::ADMIN->value;
    }

    public static function isCompanyType(string $type): bool
    {
        return in_array($type, [
            self::COMPANY_DIRECTOR->value,
            self::COMPANY_EMPLOYEE->value,
        ]);
    }
}
