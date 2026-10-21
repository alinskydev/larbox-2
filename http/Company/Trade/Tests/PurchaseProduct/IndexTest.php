<?php

namespace Http\Company\Trade\Tests\PurchaseProduct;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Trade\Search\PurchaseProductSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait {
        test_show_with_deleted as private;
        test_show_only_deleted as private;
    }

    public string $searchClass = PurchaseProductSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'company_id' => 1,
            'purchase_id' => 1,
            'product_id' => 1,
            'purchase_price' => [0, 10000],
            'sale_price' => [0, 10000],
            'quantity' => [0, 100],
        ]);
    }
}
