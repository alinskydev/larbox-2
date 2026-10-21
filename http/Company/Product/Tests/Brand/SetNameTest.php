<?php

namespace Http\Company\Product\Tests\Brand;

class SetNameTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '1/set-name/qwe',
        );
    }

    public function test_error___not_available(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '2/set-name/qwe',
            assertStatus: 400,
        );
    }
}
