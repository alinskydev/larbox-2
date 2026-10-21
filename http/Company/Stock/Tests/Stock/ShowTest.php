<?php

namespace Http\Company\Stock\Tests\Stock;

class ShowTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: '1',
        );
    }
}
