<?php

namespace Http\Company\Trade\Tests\SaleTransaction;

class ShowTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: '1',
        );
    }
}
