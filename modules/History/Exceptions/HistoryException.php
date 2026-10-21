<?php

namespace Modules\History\Exceptions;

use App\Exceptions\BusinessException;

class HistoryException extends BusinessException
{
    public const MAX_INTERVAL_EXCEEDED = 'max_interval_exceeded';

    protected function messages(): array
    {
        return [
            self::MAX_INTERVAL_EXCEEDED => 'Максимальный интервал - 24 часа',
        ];
    }
}
