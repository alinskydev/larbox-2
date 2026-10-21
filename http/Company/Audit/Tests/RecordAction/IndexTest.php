<?php

namespace Http\Company\Audit\Tests\RecordAction;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\Audit\Enums\RecordActionTypeEnum;
use Modules\Audit\Search\RecordActionSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait;

    public string $searchClass = RecordActionSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'type' => RecordActionTypeEnum::CREATE->value,
            'created_at' => [
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d'),
            ],
        ]);
    }

    public function test_filter_auditable_type___product(): void
    {
        $this->sendRequestWithFilters([
            'auditable_type' => 'product',
            'auditable_id' => 1,
        ]);
    }

    public function test_filter_auditable_type___product_specification(): void
    {
        $this->sendRequestWithFilters([
            'auditable_type' => 'product_specification',
            'auditable_id' => 1,
        ]);
    }
}
