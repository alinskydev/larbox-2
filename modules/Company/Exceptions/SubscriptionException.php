<?php

namespace Modules\Company\Exceptions;

use App\Exceptions\BusinessException;

class SubscriptionException extends BusinessException
{
    public const ATTACHED_TO_COMPANIES = 'attached_to_companies';
    public const RECORD_LIMIT_EXCEEDED = 'record_limit_exceeded';
    public const RECORD_LIMIT_WILL_BE_EXCEEDED = 'record_limit_will_be_exceeded';

    protected function messages(): array
    {
        return [
            self::ATTACHED_TO_COMPANIES => 'Уровень подписки ":subscription" не подлежит удалению, так как он привязан к компаниям ":companies"',
            self::RECORD_LIMIT_EXCEEDED => 'Вы исчерпали лимит по своему уровню подписки в :limit записей для раздела ":entity"',
            self::RECORD_LIMIT_WILL_BE_EXCEEDED => 'Вы преодолеете лимит по своему уровню подписки в :limit записей для раздела ":entity"',
        ];
    }
}
