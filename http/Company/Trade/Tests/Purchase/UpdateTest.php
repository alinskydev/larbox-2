<?php

namespace Http\Company\Trade\Tests\Purchase;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'stock_id' => 2,
            ],
        );
    }

    public function test_error___not_pending(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '2',
            body: [
                'stock_id' => 2,
            ],
            assertStatus: 400,
        );
    }
}
