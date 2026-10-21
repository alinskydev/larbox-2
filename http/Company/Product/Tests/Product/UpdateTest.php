<?php

namespace Http\Company\Product\Tests\Product;

use App\Testing\Feature\Helpers\FormHelper;

class UpdateTest extends _TestCase
{
    public function test_product(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'category_id' => 4,
                'brand_id' => 2,
                'name' => 'Product 1',
                'description' => 'Description 1',
                'barcode' => '0000000000001',
                ...FormHelper::deletableFiles(
                    field: 'image_list',
                    files: [
                        FormHelper::realImage(),
                        FormHelper::realImage(),
                    ],
                    oldKeys: [1],
                ),

                'options' => [1, 2],

                ...FormHelper::seoMeta(),
            ],
        );
    }

    public function test_variation(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '2',
            body: [
                'name' => 'Product 1-1',
                'description' => 'Description 1-1',
                'barcode' => '0000000000011',
                ...FormHelper::deletableFiles(
                    field: 'image_list',
                    files: [
                        FormHelper::realImage(),
                        FormHelper::realImage(),
                    ],
                    oldKeys: [1],
                ),

                'options' => [1, 2],

                ...FormHelper::seoMeta(),
            ],
        );
    }
}
