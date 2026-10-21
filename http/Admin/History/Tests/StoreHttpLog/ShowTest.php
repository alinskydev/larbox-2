<?php

namespace Http\Admin\History\Tests\StoreHttpLog;

class ShowTest extends _TestCase
{
    public function test_error___not_found(): void
    {
        $this->sendRequest(
            path: '1',
            assertStatus: 400,
        );
    }
}
