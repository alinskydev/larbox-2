<?php

namespace Http\Company\Product\Tests\Option;

use App\Testing\Feature\Helpers\FormHelper;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'specification_id' => 1,
                'name' => FormHelper::localized('Option 1'),
            ],
        );
    }
}
