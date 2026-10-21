<?php

namespace Http\Company\Trade\Tests\Purchase;

class CancelTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '1/cancel',
        );
    }

    public function test_error___not_pending(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '2/cancel',
            assertStatus: 400,
        );
    }
}
