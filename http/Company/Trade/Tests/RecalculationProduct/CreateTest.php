<?php

namespace Http\Company\Trade\Tests\RecalculationProduct;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'product_id' => 1,
                'price' => 1500,
            ],
        );
    }
}
