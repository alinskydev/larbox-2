<?php

namespace Modules\Company\Tests\Subscription;

use Modules\Company\Exceptions\SubscriptionException;
use Modules\Company\Models\Subscription;

class DeleteTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->deleteSubscription(2),
            expected: [
                'subscriptions_count' => [2, 1],
            ],
        );
    }

    public function test_error___attached_to_companies(): void
    {
        $this->assertChanges(
            callback: fn() => $this->deleteSubscription(1),
            error: SubscriptionException::ATTACHED_TO_COMPANIES,
        );
    }

    private function deleteSubscription(int $id): void
    {
        Subscription::query()->findOrFail($id)->safelyDelete();
    }
}
