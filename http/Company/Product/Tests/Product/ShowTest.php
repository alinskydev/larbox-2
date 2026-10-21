<?php

namespace Http\Company\Product\Tests\Product;

class ShowTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: '1',
        );
    }

    public function test_format_pdf(): void
    {
        $this->sendRequest(
            path: '1',
            query: [
                'format' => 'pdf',
            ],
        );
    }
}
