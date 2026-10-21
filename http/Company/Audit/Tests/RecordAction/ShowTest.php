<?php

namespace Http\Company\Audit\Tests\RecordAction;

class ShowTest extends _TestCase
{
    public function test_error___not_found(): void
    {
        $this->sendRequest(
            path: '1',
            query: [
                'auditable_type' => 'product',
            ],
            assertStatus: 400,
        );
    }
}
