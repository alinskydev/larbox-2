<?php

namespace Http\Company\Product\Tests\Specification;

use App\Testing\Feature\Helpers\FormHelper;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'name' => FormHelper::localized('Specification 3'),
                'description' => FormHelper::localized('Description 3'),

                'options' => array_map(
                    fn($index) => [
                        'name' => FormHelper::localized("Option $index"),
                    ],
                    range(1, 2),
                ),
            ],
        );
    }
}
