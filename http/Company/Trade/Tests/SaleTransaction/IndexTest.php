<?php

namespace Http\Company\Trade\Tests\SaleTransaction;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Trade\Enums\SaleTransactionPaymentTypeEnum;
use Modules\Trade\Enums\SaleTransactionTypeEnum;
use Modules\Trade\Search\SaleTransactionSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait {
        test_show_with_deleted as private;
        test_show_only_deleted as private;
    }

    public string $searchClass = SaleTransactionSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'company_id' => 1,
            'sale_id' => 1,
            'type' => SaleTransactionTypeEnum::PAYMENT->value,
            'payment_type' => SaleTransactionPaymentTypeEnum::CASH->value,
            'amount' => [0, 10000],
            'created_at' => [
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d'),
            ],
        ]);
    }
}
