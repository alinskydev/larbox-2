<?php

namespace Modules\System\Exceptions;

use App\Exceptions\BusinessException;

class DailyLimitException extends BusinessException
{
    public const EXCEEDED = 'exceeded';
    public const WILL_BE_EXCEEDED = 'will_be_exceeded';

    protected function messages(): array
    {
        return [
            self::EXCEEDED => 'Ежедневный лимит исчерпан',
            self::WILL_BE_EXCEEDED => 'Ежедневный лимит будет превышен',
        ];
    }
}
