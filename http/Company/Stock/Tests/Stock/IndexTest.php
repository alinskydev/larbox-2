<?php

namespace Http\Company\Stock\Tests\Stock;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Stock\Search\StockSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait;

    public string $searchClass = StockSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 2,
            'company_id' => 1,
            'name' => 'stock',
            'created_at' => [
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d'),
            ],
        ]);
    }

    public function test_available_shows(): void
    {
        $this->sendRequest(
            query: [
                'show' => [
                    'product_quantity',
                ],
            ],
        );
    }
}
