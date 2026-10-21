<?php

namespace Modules\Trade\Tests\Sale;

use Modules\Product\Models\Price;
use Modules\Stock\Exceptions\StockException;
use Modules\Trade\Enums\SaleTransactionTypeEnum;
use Modules\Trade\Exceptions\SaleException;
use Modules\Trade\Models\Sale;
use Modules\Trade\Models\SaleTransaction;

class CreateTest extends _TestCase
{
    protected function getValues(): array
    {
        return [
            ...parent::getValues(),
            'sales_count' => Sale::query()->count(),
            'payments_amount_sum' => (float)SaleTransaction::query()
                ->where('type', SaleTransactionTypeEnum::PAYMENT->value)
                ->sum('amount'),
        ];
    }

    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->createSale(quantity: 2, amount: 2000),
            expected: [
                'stock.batch_1.product_1.quantity' => [1, 0],
                'stock.batch_2.product_1.quantity' => [1.2, 0.2],
                'sales_count' => [2, 3],
                'payments_amount_sum' => [7000, 9000],
            ],
        );
    }

    public function test_error___not_enough_product(): void
    {
        $this->assertChanges(
            callback: fn() => $this->createSale(quantity: 3, amount: 3000),
            error: StockException::NOT_ENOUGH_PRODUCT,
        );
    }

    public function test_error___price_not_set(): void
    {
        Price::query()->where('product_id', 1)->delete();

        $this->assertChanges(
            callback: fn() => $this->createSale(quantity: 2, amount: 2000),
            error: SaleException::PRICE_NOT_SET,
        );
    }

    public function test_error___wrong_payments_amount(): void
    {
        $this->assertChanges(
            callback: fn() => $this->createSale(quantity: 2, amount: 1500),
            error: SaleException::WRONG_PAYMENTS_AMOUNT,
        );
    }
}
