<?php

namespace Http\Company\Trade\Tests\Purchase;

class AddProductsTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: '1/add-products',
            body: [
                'products' => [
                    $this->product(1),
                    $this->product(3),
                ],
            ],
        );
    }

    public function test_error___too_many_products(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: '1/add-products',
            body: [
                'products' => array_map(fn($index) => $this->product($index), range(1, 501)),
            ],
            assertStatus: 422,
        );
    }

    public function test_error___invalid_product(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: '1/add-products',
            body: [
                'products' => [
                    $this->product(999999),
                ],
            ],
            assertStatus: 422,
        );
    }

    public function test_error___not_pending(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: '2/add-products',
            body: [
                'products' => [
                    $this->product(1),
                ],
            ],
            assertStatus: 400,
        );
    }

    private function product(int $productId): array
    {
        return [
            'product_id' => $productId,
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'quantity' => 2,
        ];
    }
}
