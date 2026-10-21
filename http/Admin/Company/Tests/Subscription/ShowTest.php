<?php

namespace Http\Admin\Company\Tests\Subscription;

class ShowTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: '1',
        );
    }
}
