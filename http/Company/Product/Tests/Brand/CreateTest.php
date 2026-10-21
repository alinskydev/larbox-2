<?php

namespace Http\Company\Product\Tests\Brand;

use App\Testing\Feature\Helpers\FormHelper;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'name' => 'Brand 3',
                ...FormHelper::deletableFiles(
                    field: 'image',
                    files: FormHelper::realImage(),
                    oldKeys: [],
                ),

                ...FormHelper::seoMeta(),
            ],
        );
    }
}
