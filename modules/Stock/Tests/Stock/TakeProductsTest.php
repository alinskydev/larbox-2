<?php

namespace Modules\Stock\Tests\Stock;

use Modules\Stock\Exceptions\StockException;

class TakeProductsTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->takeProducts(quantity: 2),
            expected: [
                'stock.batch_1.product_1.quantity' => [1, 0],
                'stock.batch_2.product_1.quantity' => [1.2, 0.2],
            ],
        );
    }

    public function test_error___not_enough_product(): void
    {
        $this->assertChanges(
            callback: fn() => $this->takeProducts(quantity: 3),
            error: StockException::NOT_ENOUGH_PRODUCT,
        );
    }

    private function takeProducts(float $quantity): void
    {
        $products = [
            ['product_id' => 1, 'quantity' => $quantity],
        ];

        $this->getStock()->getService()->takeProducts($products);
    }
}
