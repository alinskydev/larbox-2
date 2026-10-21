<?php

namespace Modules\User\Exceptions;

use App\Exceptions\BusinessException;

class UserException extends BusinessException
{
    public const AUTH_AS_NOT_COMPANY_USER = 'auth_as_not_company_user';
    public const SELF_DELETION = 'self_deletion';
    public const UNDELETABLE = 'undeletable';
    public const UPDATING_FORBIDDEN = 'updating_forbidden';

    protected function messages(): array
    {
        return [
            self::AUTH_AS_NOT_COMPANY_USER => 'Авторизоваться можно только от лица директора или сотрудника',
            self::SELF_DELETION => 'Вы не можете удалить себя',
            self::UNDELETABLE => 'Данная запись не подлежит удалению',
            self::UPDATING_FORBIDDEN => 'Редактирование данной записи запрещено',
        ];
    }
}
