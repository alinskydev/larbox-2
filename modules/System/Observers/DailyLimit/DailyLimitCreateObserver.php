<?php

namespace Modules\System\Observers\DailyLimit;

use App\Base\Model;
use Modules\System\Enums\DailyLimitActionTypeEnum;
use Modules\System\Enums\DailyLimitEntityTypeEnum;
use Modules\System\Helpers\DailyLimitHelper;
use Modules\User\Enums\UserTypeEnum;

class DailyLimitCreateObserver
{
    public function creating(Model $model, int $decreaseValue = 1): void
    {
        $dailyLimit = $model->getService()->checkAndGetObserverParams('daily_limit');

        if (!$model->shouldHandleDailyLimit || !user()) return;

        switch (user()->type) {
            case UserTypeEnum::COMPANY_DIRECTOR->value:
            case UserTypeEnum::COMPANY_EMPLOYEE->value:
                $entityType = DailyLimitEntityTypeEnum::COMPANY;
                $entityId = $model->company_id;
                break;

            default:
                return;
        }

        DailyLimitHelper::decrease(
            entityType: $entityType,
            entityId: $entityId,
            actionType: DailyLimitActionTypeEnum::RECORD,
            actionName: $model::class,
            defaultValue: $dailyLimit,
            decreaseValue: $decreaseValue,
        );
    }
}
