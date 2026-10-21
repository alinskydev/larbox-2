<?php

namespace Modules\Trade\Services;

use App\Base\ModelService;
use App\Helpers\Database\DatabaseTransactionHelper;
use Modules\Product\Models\Price;
use Modules\Stock\Models\Batch;
use Modules\Stock\Models\Product as StockProduct;
use Modules\Trade\Enums\PurchaseStatusEnum;
use Modules\Trade\Exceptions\PurchaseException;
use Modules\Trade\Models\Purchase;
use Modules\Trade\Models\PurchaseProduct;

/**
 * @property Purchase $model
 */
class PurchaseService extends ModelService
{
    /**
     * @param array<array{product_id: int, purchase_price: float, sale_price: float, quantity: float}> $products
     */
    public function addProducts(array $products): void
    {
        $rows = [];

        foreach ($products as $product) {
            $rows[] = [
                'company_id' => $this->model->company_id,
                'purchase_id' => $this->model->id,
                'product_id' => $product['product_id'],
                'purchase_price' => $product['purchase_price'],
                'sale_price' => $product['sale_price'],
                'quantity' => $product['quantity'],
            ];
        }

        $this->model->safelyDBProcess(function () use ($rows) {
            $this->lockAndEnsurePending();

            PurchaseProduct::query()->upsert(
                values: $rows,
                uniqueBy: ['company_id', 'purchase_id', 'product_id'],
                update: ['purchase_price', 'sale_price', 'quantity'],
            );
        });
    }

    public function removeProducts(array $productIds): void
    {
        $this->model->safelyDBProcess(function () use ($productIds) {
            $this->lockAndEnsurePending();

            $this->model->products()
                ->where('company_id', $this->model->company_id)
                ->whereIn('product_id', $productIds)
                ->delete();
        });
    }

    public function createBatch(): void
    {
        if (!$this->model->products()->exists()) {
            throw new PurchaseException(PurchaseException::NO_PRODUCTS);
        }

        $batch = new Batch();
        $batch->safelySave([
            'company_id' => $this->model->company_id,
            'stock_id' => $this->model->stock_id,
        ]);

        $this->model->batch_id = $batch->id;

        $columns = ['company_id', 'stock_id', 'batch_id', 'product_id', 'purchase_price', 'quantity'];
        $bindings = [$this->model->stock_id, $batch->id];

        $productsQuery = PurchaseProduct::query()
            ->select('company_id')
            ->selectRaw('cast(? as bigint), cast(? as bigint)', $bindings)
            ->addSelect(['product_id', 'purchase_price', 'quantity'])
            ->where('company_id', $this->model->company_id)
            ->where('purchase_id', $this->model->id);

        StockProduct::query()->insertUsing(columns: $columns, query: $productsQuery);
    }

    public function updateProductPrices(): void
    {
        $rows = PurchaseProduct::query()
            ->select(['company_id', 'product_id', 'sale_price as price'])
            ->where('company_id', $this->model->company_id)
            ->where('purchase_id', $this->model->id)
            ->getQuery()
            ->get()
            ->map(fn($row) => (array)$row)
            ->all();

        Price::query()->upsert(
            values: $rows,
            uniqueBy: ['company_id', 'product_id'],
            update: ['price'],
        );
    }

    public function lockAndEnsurePending(): void
    {
        DatabaseTransactionHelper::lockModel($this->model);

        $status = Purchase::query()->whereKey($this->model->id)->value('status');

        if ($status !== PurchaseStatusEnum::PENDING->value) {
            throw new PurchaseException(PurchaseException::NOT_PENDING, [
                'status' => PurchaseStatusEnum::labelByValue(PurchaseStatusEnum::PENDING->value),
            ]);
        }
    }
}
