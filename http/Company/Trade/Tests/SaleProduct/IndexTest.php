<?php

namespace Http\Company\Trade\Tests\SaleProduct;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Trade\Search\SaleProductSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait {
        test_show_with_deleted as private;
        test_show_only_deleted as private;
    }

    public string $searchClass = SaleProductSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'company_id' => 1,
            'sale_id' => 1,
            'batch_id' => 1,
            'product_id' => 1,
            'purchase_price' => [0, 10000],
            'sale_price' => [0, 10000],
            'sold_quantity' => [0, 100],
            'returned_quantity' => [0, 100],
        ]);
    }
}
