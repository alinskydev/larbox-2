<?php

namespace Http\Admin\History\Tests\StoreHttpLog;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\History\Search\StoreHttpLogSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait {
        test_available_relations as private;
        test_show_with_deleted as private;
        test_show_only_deleted as private;
    }

    public string $searchClass = StoreHttpLogSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 2,
            'company_id' => 1,
            'request_path' => 'product/create',
            'response_status' => 200,
            'created_at' => [
                date('Y-m-d H:i:s', strtotime('-1 day')),
                date('Y-m-d H:i:s'),
            ],

            'product_create___name' => 'product',
            'product_create___brand_name' => 'brand',
        ]);
    }
}
