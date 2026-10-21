<?php

namespace Http\Company\Company\Tests\Company;

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
            'name' => 'company',
        ]);
    }

    public function test_filter___roots(): void
    {
        $this->sendRequestWithFilters([
            'is_root' => 1,
        ]);
    }

    public function test_filter___children(): void
    {
        $this->sendRequestWithFilters([
            'is_root' => 0,
        ]);
    }
}
