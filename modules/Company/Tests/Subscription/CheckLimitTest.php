<?php

namespace Modules\Company\Tests\Subscription;

use Modules\Company\Exceptions\SubscriptionException;
use Modules\Company\Models\Company;
use Modules\Company\Observers\Subscription\SubscriptionCheckLimitObserver;
use Modules\Product\Models\Brand;

class CheckLimitTest extends _TestCase
{
    public int $userId = 2;

    protected function getValues(): array
    {
        return [
            ...parent::getValues(),
            'brands_count' => Brand::query()->count(),
        ];
    }

    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->createBrand(),
            expected: [
                'brands_count' => [2, 3],
            ],
        );
    }

    public function test_error___record_limit_exceeded(): void
    {
        Company::query()->whereKey(1)->update(['subscription_id' => 2]);

        $this->assertChanges(
            callback: fn() => $this->createBrand(),
            error: SubscriptionException::RECORD_LIMIT_EXCEEDED,
        );
    }

    public function test_error___record_limit_will_be_exceeded(): void
    {
        $brand = new Brand(['company_id' => 1]);

        $this->assertChanges(
            callback: fn() => (new SubscriptionCheckLimitObserver())->creating($brand, additionalCount: 1000000),
            error: SubscriptionException::RECORD_LIMIT_WILL_BE_EXCEEDED,
        );
    }

    private function createBrand(): void
    {
        (new Brand())->safelySave(['name' => 'Brand 3']);
    }
}
