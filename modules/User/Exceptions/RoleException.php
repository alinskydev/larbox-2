<?php

namespace Modules\User\Exceptions;

use App\Exceptions\BusinessException;

class RoleException extends BusinessException
{
    public const ATTACHED_TO_USERS = 'attached_to_users';
    public const OWN_ROLE_UPDATING = 'own_role_updating';
    public const UNDELETABLE = 'undeletable';
    public const UPDATING_FORBIDDEN = 'updating_forbidden';

    protected function messages(): array
    {
        return [
            self::ATTACHED_TO_USERS => 'Роль ":role" не подлежит удалению, так как она привязана к пользователям ":users"',
            self::OWN_ROLE_UPDATING => 'Нельзя редактировать собственную роль',
            self::UNDELETABLE => 'Данная запись не подлежит удалению',
            self::UPDATING_FORBIDDEN => 'Редактирование данной записи запрещено',
        ];
    }
}
