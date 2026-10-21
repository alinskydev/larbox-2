<?php

namespace Http\Company\Product\Tests\Category;

class TreeTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: 'tree',
        );
    }
}
