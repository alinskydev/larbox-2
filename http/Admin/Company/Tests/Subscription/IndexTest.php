<?php

namespace Http\Admin\Company\Tests\Subscription;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Company\Search\SubscriptionSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait;

    public string $searchClass = SubscriptionSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 1,
            'name' => 'subscription',
            'created_at' => [
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d'),
            ],
        ]);
    }
}
