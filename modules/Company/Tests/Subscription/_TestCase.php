<?php

namespace Modules\Company\Tests\Subscription;

use App\Testing\Domain\DomainTestCase;
use Modules\Company\Models\Subscription;

class _TestCase extends DomainTestCase
{
    public int $userId = 1;

    protected function getValues(): array
    {
        return [
            'subscriptions_count' => Subscription::query()->count(),
        ];
    }
}
