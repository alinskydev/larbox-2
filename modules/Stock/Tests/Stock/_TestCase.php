<?php

namespace Modules\Stock\Tests\Stock;

use App\Testing\Domain\DomainTestCase;
use Modules\Stock\Models\Product as StockProduct;
use Modules\Stock\Models\Stock;

class _TestCase extends DomainTestCase
{
    public int $userId = 2;

    protected function getValues(): array
    {
        return StockProduct::query()
            ->get(['batch_id', 'product_id', 'quantity'])
            ->pluck(
                fn($value) => (float)$value->quantity,
                fn($key) => "stock.batch_{$key->batch_id}.product_{$key->product_id}.quantity",
            )
            ->toArray();
    }

    protected function getStock(): Stock
    {
        return Stock::query()->findOrFail(1);
    }
}
