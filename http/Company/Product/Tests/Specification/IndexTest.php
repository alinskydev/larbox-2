<?php

namespace Http\Company\Product\Tests\Specification;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Product\Search\SpecificationSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait;

    public string $searchClass = SpecificationSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 2,
            'company_id' => 1,
            'name' => 'specification',
            'created_at' => [
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d'),
            ],
        ]);
    }
}
