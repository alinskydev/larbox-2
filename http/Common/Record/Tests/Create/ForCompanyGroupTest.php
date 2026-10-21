<?php

namespace Http\Common\Record\Tests\Create;

use App\Testing\Feature\Helpers\FormHelper;

class ForCompanyGroupTest extends _TestCase
{
    public int $userId = 2;

    public function test_type___product_brand(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'product_brand',
            body: [
                'name' => 'Brand 3',
            ],
        );
    }

    public function test_type___product_option(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'product_option',
            body: [
                'specification_id' => 2,
                'name' => FormHelper::localized('Option 1'),
            ],
        );
    }

    public function test_type___product_specification(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'product_specification',
            body: [
                'name' => FormHelper::localized('Specification 3'),
            ],
        );
    }
}
