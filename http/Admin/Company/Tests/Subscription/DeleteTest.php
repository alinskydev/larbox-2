<?php

namespace Http\Admin\Company\Tests\Subscription;

class DeleteTest extends _TestCase
{
    public function test_delete(): void
    {
        $this->sendRequest(
            method: 'DELETE',
            path: '2',
        );
    }

    public function test_error___undeletable(): void
    {
        $this->sendRequest(
            method: 'DELETE',
            path: '1',
            assertStatus: 400,
        );
    }
}
