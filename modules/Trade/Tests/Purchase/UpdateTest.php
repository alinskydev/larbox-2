<?php

namespace Modules\Trade\Tests\Purchase;

use Modules\Trade\Exceptions\PurchaseException;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getPurchase(1)->safelySave(['stock_id' => 2]),
            expected: [
                'purchase.stock_id' => [1, 2],
            ],
        );
    }

    public function test_error___not_pending(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getPurchase(2)->safelySave(['stock_id' => 2]),
            error: PurchaseException::NOT_PENDING,
        );
    }
}
