<?php

namespace Http\Company\Product\Tests\Option;

use App\Testing\Feature\Helpers\FormHelper;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'specification_id' => 1,
                'name' => FormHelper::localized('Option 3'),
            ],
        );
    }
}
