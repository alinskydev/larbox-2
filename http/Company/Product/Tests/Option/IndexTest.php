<?php

namespace Http\Company\Product\Tests\Option;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Product\Search\OptionSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait;

    public string $searchClass = OptionSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 2,
            'company_id' => 1,
            'specification_id' => 1,
            'name' => 'option',
            'created_at' => [
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d'),
            ],
        ]);
    }
}
