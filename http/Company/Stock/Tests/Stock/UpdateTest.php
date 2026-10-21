<?php

namespace Http\Company\Stock\Tests\Stock;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'name' => 'Stock 1',
            ],
        );
    }
}
