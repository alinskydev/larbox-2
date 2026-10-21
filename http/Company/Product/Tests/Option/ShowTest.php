<?php

namespace Http\Company\Product\Tests\Option;

class ShowTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: '2',
        );
    }
}
