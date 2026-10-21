<?php

namespace Http\Company\Trade\Tests\Sale;

use Modules\Trade\Enums\SaleTransactionPaymentTypeEnum;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: $this->body(quantity: 2, amount: 2000),
        );
    }

    public function test_error___not_enough_stock(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: $this->body(quantity: 3, amount: 3000),
            assertStatus: 400,
        );
    }

    public function test_error___wrong_amount(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: $this->body(quantity: 2, amount: 1500),
            assertStatus: 400,
        );
    }

    private function body(float $quantity, float $amount): array
    {
        return [
            'stock_id' => 1,
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => $quantity,
                ],
            ],
            'transactions' => [
                [
                    'payment_type' => SaleTransactionPaymentTypeEnum::CASH->value,
                    'amount' => $amount,
                ],
            ],
        ];
    }
}
