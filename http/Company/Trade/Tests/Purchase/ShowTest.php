<?php

namespace Http\Company\Trade\Tests\Purchase;

class ShowTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: '1',
        );
    }
}
