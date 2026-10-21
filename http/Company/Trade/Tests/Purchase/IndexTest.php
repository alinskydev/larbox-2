<?php

namespace Http\Company\Trade\Tests\Purchase;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Trade\Enums\PurchaseStatusEnum;
use Modules\Trade\Search\PurchaseSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait {
        test_show_with_deleted as private;
        test_show_only_deleted as private;
    }

    public string $searchClass = PurchaseSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 2,
            'company_id' => 1,
            'stock_id' => 1,
            'batch_id' => 1,
            'status' => PurchaseStatusEnum::PENDING->value,
            'created_at' => [
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d'),
            ],
        ]);
    }
}
