<?php

namespace Http\Company\Trade\Tests\Purchase;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'stock_id' => 1,
            ],
        );
    }
}
