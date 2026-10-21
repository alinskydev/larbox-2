<?php

namespace Http\Company\Trade\Tests\Sale;

use Modules\Trade\Enums\SaleTransactionPaymentTypeEnum;

class ReturnTest extends _TestCase
{
    public function test_success___products(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '1/return',
            body: [
                'products' => [
                    [
                        'id' => 1,
                        'quantity' => 1,
                    ],
                ],
            ],
        );
    }

    public function test_success___transactions(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '1/return',
            body: [
                'transactions' => [
                    [
                        'payment_type' => SaleTransactionPaymentTypeEnum::CASH->value,
                        'amount' => 1000,
                    ],
                    [
                        'payment_type' => SaleTransactionPaymentTypeEnum::CARD->value,
                        'amount' => 400,
                    ],
                ],
            ],
        );
    }

    public function test_error___products_quantity_exceeds(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '1/return',
            body: [
                'products' => [
                    [
                        'id' => 1,
                        'quantity' => 2,
                    ],
                ],
            ],
            assertStatus: 400,
        );
    }

    public function test_error___transactions_amount_exceeds(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '1/return',
            body: [
                'transactions' => [
                    [
                        'payment_type' => SaleTransactionPaymentTypeEnum::CASH->value,
                        'amount' => 3000,
                    ],
                ],
            ],
            assertStatus: 400,
        );
    }
}
