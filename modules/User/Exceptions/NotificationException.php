<?php

namespace Modules\User\Exceptions;

use App\Exceptions\BusinessException;

class NotificationException extends BusinessException
{
    public const FILE_DELETED = 'file_deleted';

    protected function messages(): array
    {
        return [
            self::FILE_DELETED => 'Файл был удалён',
        ];
    }
}
