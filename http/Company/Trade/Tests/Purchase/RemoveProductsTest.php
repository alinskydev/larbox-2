<?php

namespace Http\Company\Trade\Tests\Purchase;

class RemoveProductsTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '1/remove-products',
            body: [
                'product_ids' => [1],
            ],
        );
    }

    public function test_error___not_pending(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '2/remove-products',
            body: [
                'product_ids' => [1],
            ],
            assertStatus: 400,
        );
    }
}
