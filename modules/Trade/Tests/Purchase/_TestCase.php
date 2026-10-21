<?php

namespace Modules\Trade\Tests\Purchase;

use App\Testing\Domain\DomainTestCase;
use Modules\Product\Models\Price;
use Modules\Stock\Models\Product as StockProduct;
use Modules\Trade\Enums\PurchaseStatusEnum;
use Modules\Trade\Models\Purchase;

class _TestCase extends DomainTestCase
{
    public int $userId = 2;

    protected function getValues(): array
    {
        $purchase = $this->getPurchase(1);

        $stock = StockProduct::query()
            ->selectRaw('product_id, sum(quantity) as quantity')
            ->groupBy('product_id')
            ->get()
            ->pluck(
                fn($value) => (float)$value->quantity,
                fn($key) => "stock.product_{$key->product_id}.quantity_sum",
            )
            ->toArray();

        $purchaseProducts = $purchase->products()
            ->get(['product_id', 'quantity'])
            ->pluck(
                fn($value) => (float)$value->quantity,
                fn($key) => "purchase.product_{$key->product_id}.quantity",
            )
            ->toArray();

        $prices = Price::query()
            ->get(['product_id', 'price'])
            ->pluck(
                fn($value) => (float)$value->price,
                fn($key) => "product_{$key->product_id}.price",
            )
            ->toArray();

        return [
            ...$stock,
            ...$purchaseProducts,
            ...$prices,
            'purchase.stock_id' => $purchase->stock_id,
            'purchase.status' => PurchaseStatusEnum::from($purchase->status),
        ];
    }

    protected function getPurchase(int $id): Purchase
    {
        return Purchase::query()->findOrFail($id);
    }
}
