<?php

namespace Modules\Trade\Tests\Purchase;

use Modules\Trade\Enums\PurchaseStatusEnum;
use Modules\Trade\Exceptions\PurchaseException;

class CancelTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->cancel(1),
            expected: [
                'purchase.status' => [PurchaseStatusEnum::PENDING, PurchaseStatusEnum::CANCELLED],
            ],
        );
    }

    public function test_error___not_pending(): void
    {
        $this->assertChanges(
            callback: fn() => $this->cancel(2),
            error: PurchaseException::NOT_PENDING,
        );
    }

    private function cancel(int $purchaseId): void
    {
        $this->getPurchase($purchaseId)->safelySave(['status' => PurchaseStatusEnum::CANCELLED->value]);
    }
}
