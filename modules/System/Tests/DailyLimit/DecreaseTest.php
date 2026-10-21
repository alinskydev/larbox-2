<?php

namespace Modules\System\Tests\DailyLimit;

use Modules\System\Enums\DailyLimitActionTypeEnum;
use Modules\System\Enums\DailyLimitEntityTypeEnum;
use Modules\System\Enums\DailyLimitIntegrationActionNameEnum;
use Modules\System\Exceptions\DailyLimitException;
use Modules\System\Helpers\DailyLimitHelper;

class DecreaseTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->decrease(decreaseValue: 1),
            expected: [
                'daily_limit.remaining' => [null, 9],
            ],
        );
    }

    public function test_error___will_be_exceeded(): void
    {
        $this->assertChanges(
            callback: fn() => $this->decrease(decreaseValue: 11),
            error: DailyLimitException::WILL_BE_EXCEEDED,
        );
    }

    private function decrease(int $decreaseValue): void
    {
        DailyLimitHelper::decrease(
            entityType: DailyLimitEntityTypeEnum::COMPANY,
            entityId: 1,
            actionType: DailyLimitActionTypeEnum::INTEGRATION,
            actionName: DailyLimitIntegrationActionNameEnum::STORE_PRODUCT->value,
            defaultValue: 10,
            decreaseValue: $decreaseValue,
        );
    }
}
