<?php

namespace Http\Company\Product\Tests\Option;

class DeleteTest extends _TestCase
{
    public function test_error___undeletable(): void
    {
        $this->sendRequest(
            method: 'DELETE',
            path: '1',
            assertStatus: 400,
        );
    }
}
