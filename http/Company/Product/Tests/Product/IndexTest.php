<?php

namespace Http\Company\Product\Tests\Product;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Product\Search\ProductSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait;

    public string $searchClass = ProductSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 1,
            'company_id' => 1,
            'parent_id' => 1,
            'category_id' => 2,
            'brand_id' => 1,
            'price.price' => [0.01, 1000],
            'created_at' => [
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d'),
            ],

            'search' => 'product',
        ]);
    }

    public function test_available_shows(): void
    {
        $this->sendRequest(
            query: [
                'show' => [
                    'stock_quantity',
                ],
            ],
        );
    }

    public function test_format_excel(): void
    {
        $this->sendRequest(
            query: [
                'format' => 'excel',
                'excel_columns' => [
                    'full_text',
                    'company_id',
                    'category_id',
                    'brand_id',
                    'price',
                    'barcode',
                    'created_at',
                ],
            ],
        );
    }
}
