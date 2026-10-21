<?php

namespace Modules\Trade\Tests\Purchase;

use Modules\Trade\Exceptions\PurchaseException;

class RemoveProductsTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getPurchase(1)->getService()->removeProducts([1]),
            expected: [
                'purchase.product_1.quantity' => [5, null],
            ],
        );
    }

    public function test_error___not_pending(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getPurchase(2)->getService()->removeProducts([1]),
            error: PurchaseException::NOT_PENDING,
        );
    }
}
