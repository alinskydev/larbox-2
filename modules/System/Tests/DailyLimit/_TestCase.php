<?php

namespace Modules\System\Tests\DailyLimit;

use App\Testing\Domain\DomainTestCase;
use Modules\System\Enums\DailyLimitActionTypeEnum;
use Modules\System\Enums\DailyLimitEntityTypeEnum;
use Modules\System\Enums\DailyLimitIntegrationActionNameEnum;
use Modules\System\Models\DailyLimit;

class _TestCase extends DomainTestCase
{
    public int $userId = 2;

    protected function getValues(): array
    {
        return [
            'daily_limit.remaining' => DailyLimit::query()->where($this->keys())->value('remaining'),
        ];
    }

    protected function keys(): array
    {
        return [
            'entity_type' => DailyLimitEntityTypeEnum::COMPANY->value,
            'entity_id' => 1,
            'action_type' => DailyLimitActionTypeEnum::INTEGRATION->value,
            'action_name' => DailyLimitIntegrationActionNameEnum::STORE_PRODUCT->value,
        ];
    }
}
