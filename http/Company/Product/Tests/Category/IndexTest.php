<?php

namespace Http\Company\Product\Tests\Category;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Product\Search\CategorySearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait {
        test_available_sorts as private;
    }

    public string $searchClass = CategorySearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 3,
            'creator_id' => 2,
            'company_id' => 1,
            'parent_id' => 1,
            'name' => 'category',
        ]);
    }

    public function test_available_shows(): void
    {
        $this->sendRequest(
            query: [
                'show' => ['products_count'],
            ],
        );
    }
}
