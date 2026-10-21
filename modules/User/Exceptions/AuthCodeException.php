<?php

namespace Modules\User\Exceptions;

use App\Exceptions\BusinessException;

class AuthCodeException extends BusinessException
{
    public const RESEND_NOT_AVAILABLE = 'resend_not_available';

    protected function messages(): array
    {
        return [
            self::RESEND_NOT_AVAILABLE => 'Повторная отправка кода будет доступна в :time',
        ];
    }
}
