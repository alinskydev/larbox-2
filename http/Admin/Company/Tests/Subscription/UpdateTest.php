<?php

namespace Http\Admin\Company\Tests\Subscription;

use Modules\Company\Enums\SubscriptionRecordLimitEnum;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'name' => 'Subscription 1',
                'description' => 'Description 1',
                'record_limits' => array_map(fn($value) => round(rand(1000, 10000), -3), SubscriptionRecordLimitEnum::labels()),
            ],
        );
    }
}
