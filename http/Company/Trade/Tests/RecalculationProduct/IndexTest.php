<?php

namespace Http\Company\Trade\Tests\RecalculationProduct;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Trade\Search\RecalculationProductSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait {
        test_show_with_deleted as private;
        test_show_only_deleted as private;
    }

    public string $searchClass = RecalculationProductSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 2,
            'company_id' => 1,
            'product_id' => 1,
            'price' => [0, 10000],
            'created_at' => [
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d'),
            ],
        ]);
    }
}
