<?php

namespace Modules\Stock\Services;

use App\Base\ModelService;
use App\Helpers\Database\DatabaseTransactionHelper;
use Modules\Product\Models\Product as ProductProduct;
use Modules\Stock\Exceptions\StockException;
use Modules\Stock\Models\Product;
use Modules\Stock\Models\Stock;

/**
 * @property Stock $model
 */
class StockService extends ModelService
{
    /**
     * @param array<array{product_id: int, quantity: float}> $products
     * @return array<array{batch_id: int, product_id: int, purchase_price: float, quantity: float}>
     */
    public function takeProducts(array $products): array
    {
        DatabaseTransactionHelper::lockModel($this->model);

        $stockProducts = Product::query()
            ->where('company_id', $this->model->company_id)
            ->where('stock_id', $this->model->id)
            ->whereIn('product_id', array_column($products, 'product_id'))
            ->where('quantity', '>', 0)
            ->orderBy('batch_id')
            ->get()
            ->groupBy('product_id');

        $takenProducts = [];

        foreach ($products as $product) {
            $quantity = $product['quantity'];

            foreach ($stockProducts->get($product['product_id'], []) as $stockProduct) {
                if (num_lte($quantity, 0)) break;

                $takenQuantity = min($quantity, $stockProduct->quantity);
                $quantity = num_round($quantity - $takenQuantity);
                $stockProduct->decrement('quantity', $takenQuantity);

                $takenProducts[] = [
                    'batch_id' => $stockProduct->batch_id,
                    'product_id' => $stockProduct->product_id,
                    'purchase_price' => $stockProduct->purchase_price,
                    'quantity' => $takenQuantity,
                ];
            }

            if (num_gt($quantity, 0)) {
                $name = ProductProduct::query()->whereKey($product['product_id'])->value('name');
                throw new StockException(StockException::NOT_ENOUGH_PRODUCT, ['name' => $name]);
            }
        }

        return $takenProducts;
    }
}
