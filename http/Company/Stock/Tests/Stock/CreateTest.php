<?php

namespace Http\Company\Stock\Tests\Stock;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'name' => 'Stock 3',
            ],
        );
    }
}
