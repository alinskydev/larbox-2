<?php

namespace Http\Company\Product\Tests\Brand;

use App\Testing\Feature\Helpers\FormHelper;

class UpdateTest extends _TestCase
{
    public function test_with_new_image(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'name' => 'Brand 1',
                ...FormHelper::deletableFiles(
                    field: 'image',
                    files: FormHelper::realImage(),
                    oldKeys: [0],
                ),

                ...FormHelper::seoMeta(),
            ],
        );
    }

    public function test_without_new_image(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'name' => 'Brand 1',
                'image_old_keys' => '[0]',

                ...FormHelper::seoMeta(),
            ],
        );
    }

    public function test_without_old_image(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'name' => 'Brand 1',
                'image_old_keys' => '[]',

                ...FormHelper::seoMeta(),
            ],
        );
    }
}
