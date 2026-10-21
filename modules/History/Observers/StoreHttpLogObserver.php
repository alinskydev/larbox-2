<?php

namespace Modules\History\Observers;

use Modules\History\Models\StoreHttpLog;
use Modules\System\Enums\DailyLimitActionTypeEnum;
use Modules\System\Enums\DailyLimitEntityTypeEnum;
use Modules\System\Enums\DailyLimitIntegrationActionNameEnum;
use Modules\System\Helpers\DailyLimitHelper;

class StoreHttpLogObserver
{
    public function created(StoreHttpLog $model): void
    {
        $isSuccessful = $model->response_status >= 200 && $model->response_status <= 299;

        if (!$isSuccessful) return;

        switch ($model->request_path) {
            case 'product/create':
                DailyLimitHelper::decrease(
                    entityType: DailyLimitEntityTypeEnum::COMPANY,
                    entityId: $model->company_id,
                    actionType: DailyLimitActionTypeEnum::INTEGRATION,
                    actionName: DailyLimitIntegrationActionNameEnum::STORE_PRODUCT->value,
                    defaultValue: DailyLimitIntegrationActionNameEnum::limit(DailyLimitIntegrationActionNameEnum::STORE_PRODUCT),
                );

                break;
        }
    }
}
