<?php

namespace Modules\Trade\Tests\Purchase;

use Modules\Trade\Enums\PurchaseStatusEnum;
use Modules\Trade\Exceptions\PurchaseException;
use Modules\Trade\Models\PurchaseProduct;

class CompleteTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->complete(1),
            expected: [
                'stock.product_1.quantity_sum' => [2.2, 7.2],
                'stock.product_2.quantity_sum' => [2, 5],
                'product_1.price' => [1000, 1300],
                'product_2.price' => [2000, 2600],
                'purchase.status' => [PurchaseStatusEnum::PENDING, PurchaseStatusEnum::COMPLETED],
            ],
        );
    }

    public function test_error___no_products(): void
    {
        PurchaseProduct::query()->where('purchase_id', 1)->delete();

        $this->assertChanges(
            callback: fn() => $this->complete(1),
            error: PurchaseException::NO_PRODUCTS,
        );
    }

    public function test_error___not_pending(): void
    {
        $this->assertChanges(
            callback: fn() => $this->complete(2),
            error: PurchaseException::NOT_PENDING,
        );
    }

    private function complete(int $purchaseId): void
    {
        $this->getPurchase($purchaseId)->safelySave(['status' => PurchaseStatusEnum::COMPLETED->value]);
    }
}
