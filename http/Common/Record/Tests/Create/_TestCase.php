<?php

namespace Http\Common\Record\Tests\Create;

use App\Testing\Feature\PostmanTestCase;

class _TestCase extends PostmanTestCase
{
    public string $requestUrl = 'common/record';

    public function test_error___throttle(): void
    {
        $this->requestUrl = 'common/record/product_brand';

        for ($i = 0; $i <= 3; $i++) {
            $this->sendRequest(
                method: 'POST',
                body: [
                    'name' => "New brand $i",
                ],
                assertStatus: $i < 3 ? 200 : 429,
            );
        }
    }
}
