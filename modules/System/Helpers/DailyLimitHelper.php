<?php

namespace Modules\System\Helpers;

use Modules\System\Enums\DailyLimitActionTypeEnum;
use Modules\System\Enums\DailyLimitEntityTypeEnum;
use Modules\System\Exceptions\DailyLimitException;
use Modules\System\Models\DailyLimit;

class DailyLimitHelper
{
    public static function ensure(
        DailyLimitEntityTypeEnum $entityType,
        int $entityId,
        DailyLimitActionTypeEnum $actionType,
        string $actionName,
    ): void {
        $limit = DailyLimit::query()
            ->where('entity_type', $entityType->value)
            ->where('entity_id', $entityId)
            ->where('action_type', $actionType->value)
            ->where('action_name', $actionName)
            ->first();

        if ($limit && $limit->remaining <= 0) {
            throw new DailyLimitException(DailyLimitException::EXCEEDED);
        }
    }

    public static function decrease(
        DailyLimitEntityTypeEnum $entityType,
        int $entityId,
        DailyLimitActionTypeEnum $actionType,
        string $actionName,
        int $defaultValue,
        int $decreaseValue = 1,
    ): void {
        $keys = [
            'entity_type' => $entityType->value,
            'entity_id' => $entityId,
            'action_type' => $actionType->value,
            'action_name' => $actionName,
        ];

        DailyLimit::query()->getQuery()->insertOrIgnore([...$keys, 'remaining' => $defaultValue]);

        $decreased = DailyLimit::query()
            ->where($keys)
            ->where('remaining', '>=', $decreaseValue)
            ->decrement('remaining', $decreaseValue);

        if (!$decreased) {
            throw new DailyLimitException(DailyLimitException::WILL_BE_EXCEEDED);
        }
    }
}
