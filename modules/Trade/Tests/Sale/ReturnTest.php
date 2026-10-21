<?php

namespace Modules\Trade\Tests\Sale;

use Modules\Trade\Enums\SaleStatusEnum;
use Modules\Trade\Enums\SaleTransactionPaymentTypeEnum;
use Modules\Trade\Enums\SaleTransactionTypeEnum;
use Modules\Trade\Exceptions\SaleException;

class ReturnTest extends _TestCase
{
    protected function getValues(): array
    {
        $sale = $this->getSale(1);
        $returnedQuantity = $sale->products()->sum('returned_quantity');

        $refunds = $sale->transactions()
            ->where('type', SaleTransactionTypeEnum::REFUND->value)
            ->sum('amount');

        return [
            ...parent::getValues(),
            'sale.status' => SaleStatusEnum::from($sale->status),
            'sale.returned_quantity_sum' => (float)$returnedQuantity,
            'sale.refunds_amount_sum' => (float)$refunds,
        ];
    }

    public function test_success___products(): void
    {
        $this->assertChanges(
            callback: fn() => $this->returnProducts(quantity: 1),
            expected: [
                'stock.batch_1.product_1.quantity' => [1, 2],
                'sale.returned_quantity_sum' => [0, 1],
            ],
        );
    }

    public function test_success___transactions(): void
    {
        $this->assertChanges(
            callback: fn() => $this->refund(amount: 1400),
            expected: [
                'sale.status' => [SaleStatusEnum::COMPLETED, SaleStatusEnum::PARTIALLY_CANCELLED],
                'sale.refunds_amount_sum' => [0, 1400],
            ],
        );
    }

    public function test_success___transactions_full(): void
    {
        $this->assertChanges(
            callback: fn() => $this->refund(amount: 2800),
            expected: [
                'sale.status' => [SaleStatusEnum::COMPLETED, SaleStatusEnum::FULLY_CANCELLED],
                'sale.refunds_amount_sum' => [0, 2800],
            ],
        );
    }

    public function test_error___nothing_to_return(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getSale(1)->getService()->return(),
            error: SaleException::NOTHING_TO_RETURN,
        );
    }

    public function test_error___product_not_found(): void
    {
        $this->assertChanges(
            callback: fn() => $this->returnProducts(quantity: 1, id: 3),
            error: SaleException::PRODUCT_NOT_FOUND,
        );
    }

    public function test_error___refund_amount_exceeds(): void
    {
        $this->assertChanges(
            callback: fn() => $this->refund(amount: 3000),
            error: SaleException::REFUND_AMOUNT_EXCEEDS,
        );
    }

    public function test_error___return_quantity_exceeds(): void
    {
        $this->assertChanges(
            callback: fn() => $this->returnProducts(quantity: 2),
            error: SaleException::RETURN_QUANTITY_EXCEEDS,
        );
    }

    private function returnProducts(float $quantity, int $id = 1): void
    {
        $products = [
            ['id' => $id, 'quantity' => $quantity],
        ];

        $this->getSale(1)->getService()->return(products: $products);
    }

    private function refund(float $amount): void
    {
        $transactions = [
            ['payment_type' => SaleTransactionPaymentTypeEnum::CASH->value, 'amount' => $amount],
        ];

        $this->getSale(1)->getService()->return(transactions: $transactions);
    }
}
