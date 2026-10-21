<?php

namespace Http\Company\Product\Tests\Specification;

use App\Testing\Feature\Helpers\FormHelper;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'name' => FormHelper::localized('Specification 1'),
                'description' => FormHelper::localized('Description 1'),

                'options' => array_map(
                    fn($index) => [
                        'id' => $index,
                        'name' => FormHelper::localized("Option $index"),
                    ],
                    range(1, 3),
                ),
            ],
        );
    }
}
