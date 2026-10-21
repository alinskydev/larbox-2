<?php

namespace Modules\System\Exceptions;

use App\Exceptions\BusinessException;

class LanguageException extends BusinessException
{
    public const CURRENT_LANGUAGE_DEACTIVATION = 'current_language_deactivation';
    public const MAIN_LANGUAGE_DEACTIVATION = 'main_language_deactivation';

    protected function messages(): array
    {
        return [
            self::CURRENT_LANGUAGE_DEACTIVATION => 'Невозможно деактивировать текущий язык',
            self::MAIN_LANGUAGE_DEACTIVATION => 'Невозможно деактивировать основной язык',
        ];
    }
}
