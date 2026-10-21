<?php

namespace Http\Admin\Company\Tests\Subscription;

use Modules\Company\Enums\SubscriptionRecordLimitEnum;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'name' => 'Subscription 3',
                'description' => 'Description 3',
                'record_limits' => array_map(fn($value) => round(rand(1000, 10000), -3), SubscriptionRecordLimitEnum::labels()),
            ],
        );
    }
}
