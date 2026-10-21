<?php

namespace Modules\Company\Exceptions;

use App\Exceptions\BusinessException;

class CompanyException extends BusinessException
{
    public const COMPANIES_LIMIT_REACHED = 'companies_limit_reached';

    protected function messages(): array
    {
        return [
            self::COMPANIES_LIMIT_REACHED => 'Максимальное количество компаний: :limit. Свяжитесь с разработчиком для увеличения лимита.',
        ];
    }
}
