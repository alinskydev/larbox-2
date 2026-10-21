<?php

namespace Modules\System\Tests\DailyLimit;

use Modules\System\Enums\DailyLimitActionTypeEnum;
use Modules\System\Enums\DailyLimitEntityTypeEnum;
use Modules\System\Enums\DailyLimitIntegrationActionNameEnum;
use Modules\System\Exceptions\DailyLimitException;
use Modules\System\Helpers\DailyLimitHelper;
use Modules\System\Models\DailyLimit;

class EnsureTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->ensure(),
        );
    }

    public function test_error___exceeded(): void
    {
        DailyLimit::query()->insert([...$this->keys(), 'remaining' => 0]);

        $this->assertChanges(
            callback: fn() => $this->ensure(),
            error: DailyLimitException::EXCEEDED,
        );
    }

    private function ensure(): void
    {
        DailyLimitHelper::ensure(
            entityType: DailyLimitEntityTypeEnum::COMPANY,
            entityId: 1,
            actionType: DailyLimitActionTypeEnum::INTEGRATION,
            actionName: DailyLimitIntegrationActionNameEnum::STORE_PRODUCT->value,
        );
    }
}
