<?php

namespace Http\Admin\Company\Tests\Company;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Company\Search\CompanySearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait;

    public string $searchClass = CompanySearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 1,
            'parent_id' => 1,
            'subscription_id' => 1,
            'name' => 'company',
            'created_at' => [
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d'),
            ],
        ]);
    }

    public function test_filter___only_top_level(): void
    {
        $this->sendRequestWithFilters([
            'is_root' => 1,
        ]);
    }

    public function test_filter___only_children(): void
    {
        $this->sendRequestWithFilters([
            'is_root' => 0,
        ]);
    }
}
