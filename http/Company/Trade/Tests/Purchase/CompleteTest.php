<?php

namespace Http\Company\Trade\Tests\Purchase;

use Modules\Trade\Models\PurchaseProduct;

class CompleteTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '1/complete',
        );
    }

    public function test_error___not_pending(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '2/complete',
            assertStatus: 400,
        );
    }

    public function test_error___no_products(): void
    {
        PurchaseProduct::query()->where('purchase_id', 1)->delete();

        $this->sendRequest(
            method: 'PATCH',
            path: '1/complete',
            assertStatus: 400,
        );
    }
}
