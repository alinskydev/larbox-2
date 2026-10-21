<?php

namespace Http\Company\Product\Tests\Category;

class ShowTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: '2',
        );
    }
}
