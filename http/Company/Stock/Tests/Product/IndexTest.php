<?php

namespace Http\Company\Stock\Tests\Product;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Stock\Search\ProductSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait;

    public string $searchClass = ProductSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'company_id' => 1,
            'stock_id' => 1,
            'batch_id' => 1,
            'product_id' => 1,
            'purchase_price' => [0, 10000],
            'quantity' => [0, 100],
        ]);
    }
}
