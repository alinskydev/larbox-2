<?php

namespace Modules\Trade\Tests\Purchase;

use Modules\Trade\Exceptions\PurchaseException;

class AddProductsTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->addProducts(1),
            expected: [
                'purchase.product_1.quantity' => [5, 2],
                'purchase.product_3.quantity' => [null, 2],
            ],
        );
    }

    public function test_error___not_pending(): void
    {
        $this->assertChanges(
            callback: fn() => $this->addProducts(2),
            error: PurchaseException::NOT_PENDING,
        );
    }

    private function addProducts(int $purchaseId): void
    {
        $products = [
            ['product_id' => 1, 'purchase_price' => 1000, 'sale_price' => 1500, 'quantity' => 2],
            ['product_id' => 3, 'purchase_price' => 1000, 'sale_price' => 1500, 'quantity' => 2],
        ];

        $this->getPurchase($purchaseId)->getService()->addProducts($products);
    }
}
