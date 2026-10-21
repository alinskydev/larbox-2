<?php

namespace Modules\Trade\Tests\Sale;

use App\Testing\Domain\DomainTestCase;
use Modules\Stock\Models\Product as StockProduct;
use Modules\Trade\Enums\SaleStatusEnum;
use Modules\Trade\Enums\SaleTransactionPaymentTypeEnum;
use Modules\Trade\Models\Sale;

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

    protected function getSale(int $id): Sale
    {
        return Sale::query()->findOrFail($id);
    }

    protected function createSale(float $quantity, float $amount): void
    {
        $sale = new Sale();

        $sale->saleProducts = [
            ['product_id' => 1, 'quantity' => $quantity],
        ];

        $sale->salePayments = [
            ['payment_type' => SaleTransactionPaymentTypeEnum::CASH->value, 'amount' => $amount],
        ];

        $sale->safelySave([
            'stock_id' => 1,
            'status' => SaleStatusEnum::COMPLETED->value,
        ]);
    }
}
