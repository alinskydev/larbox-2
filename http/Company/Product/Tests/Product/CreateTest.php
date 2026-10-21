<?php

namespace Http\Company\Product\Tests\Product;

use App\Testing\Feature\Helpers\FormHelper;

class CreateTest extends _TestCase
{
    public function test_product(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'category_id' => 4,
                'brand_id' => 1,
                'name' => 'Product 3',
                'description' => 'Description 3',
                'barcode' => '0000000000003',
                ...FormHelper::deletableFiles(
                    field: 'image_list',
                    files: [
                        FormHelper::realImage(),
                        FormHelper::realImage(),
                    ],
                    oldKeys: [],
                ),

                'options' => [1, 2],

                ...FormHelper::seoMeta(),
            ],
        );
    }

    public function test_variation(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'parent_id' => 3,
                'name' => 'Product 2-1',
                'description' => 'Description 2-1',
                'barcode' => '0000000000021',
                ...FormHelper::deletableFiles(
                    field: 'image_list',
                    files: [
                        FormHelper::realImage(),
                        FormHelper::realImage(),
                    ],
                    oldKeys: [],
                ),

                'options' => [2],

                ...FormHelper::seoMeta(),
            ],
        );
    }
}
