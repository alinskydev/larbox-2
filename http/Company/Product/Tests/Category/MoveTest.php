<?php

namespace Http\Company\Product\Tests\Category;

class MoveTest extends _TestCase
{
    public function test_before(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: 'move',
            body: [
                'position' => 'before',
                'source_id' => 2,
                'destination_id' => 8,
            ],
        );
    }

    public function test_after(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: 'move',
            body: [
                'position' => 'after',
                'source_id' => 2,
                'destination_id' => 8,
            ],
        );
    }

    public function test_into(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: 'move',
            body: [
                'position' => 'into',
                'source_id' => 2,
                'destination_id' => 8,
            ],
        );
    }
}
