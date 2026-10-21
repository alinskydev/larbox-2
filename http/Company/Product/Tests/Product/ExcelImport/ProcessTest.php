<?php

namespace Http\Company\Product\Tests\Product\ExcelImport;

use Illuminate\Http\UploadedFile;

class ProcessTest extends _TestCase
{
    public function test_success(): void
    {
        $file = base_path('modules/Product/Excel/Import/Examples/products.xlsx');

        $this->sendRequest(
            method: 'POST',
            path: 'process',
            body: [
                'file' => UploadedFile::fake()->createWithContent('example.xlsx', file_get_contents($file)),
            ],
        );
    }
}
