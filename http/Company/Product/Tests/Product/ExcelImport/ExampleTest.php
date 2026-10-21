<?php

namespace Http\Company\Product\Tests\Product\ExcelImport;

class ExampleTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: 'example',
        );
    }
}
