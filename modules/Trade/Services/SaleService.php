<?php

namespace Modules\Trade\Services;

use App\Base\ModelService;
use App\Helpers\Database\DatabaseTransactionHelper;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Price;
use Modules\Product\Models\Product;
use Modules\Stock\Models\Product as StockProduct;
use Modules\Trade\Enums\SaleStatusEnum;
use Modules\Trade\Enums\SaleTransactionTypeEnum;
use Modules\Trade\Exceptions\SaleException;
use Modules\Trade\Models\Sale;
use Modules\Trade\Models\SaleProduct;

/**
 * @property Sale $model
 */
class SaleService extends ModelService
{
    /**
     * @param array<array{product_id: int, quantity: float}> $saleProducts
     */
    public function addProducts(array $saleProducts): void
    {
        $productIds = array_column($saleProducts, 'product_id');

        $products = Product::query()
            ->with(['category', 'brand', 'options'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $prices = Price::query()->whereIn('product_id', $productIds)->pluck('price', 'product_id');

        foreach ($products as $product) {
            if (!$prices->has($product->id)) {
                throw new SaleException(SaleException::PRICE_NOT_SET, [
                    'name' => $product->name,
                ]);
            }
        }

        $takenProducts = $this->model->stock->getService()->takeProducts($saleProducts);
        $rows = [];

        foreach ($takenProducts as $takenProduct) {
            $product = $products->get($takenProduct['product_id']);

            $rows[] = [
                'company_id' => $this->model->company_id,
                'sale_id' => $this->model->id,
                'batch_id' => $takenProduct['batch_id'],
                'product_id' => $takenProduct['product_id'],
                'purchase_price' => $takenProduct['purchase_price'],
                'sale_price' => $prices->get($product->id),
                'sold_quantity' => $takenProduct['quantity'],
                'product_snapshot' => json_encode($product->toArray()),
            ];
        }

        SaleProduct::query()->insert($rows);
    }

    /**
     * @param array<array{payment_type: string, amount: float}> $payments
     */
    public function addPayments(array $payments): void
    {
        $this->createTransactions(SaleTransactionTypeEnum::PAYMENT, $payments);

        $total = $this->model->products()
            ->where('company_id', $this->model->company_id)
            ->sum(DB::raw('sale_price * sold_quantity'));

        $total = round($total, 2);
        $paid = array_sum(array_column($payments, 'amount'));

        if (num_ne($total, $paid)) {
            throw new SaleException(SaleException::WRONG_PAYMENTS_AMOUNT, [
                'paid' => $paid,
                'total' => $total,
            ]);
        }
    }

    /**
     * @param array<array{id: int, quantity: float}> $products
     * @param array<array{payment_type: string, amount: float}> $transactions
     */
    public function return(array $products = [], array $transactions = []): void
    {
        if (!$products && !$transactions) {
            throw new SaleException(SaleException::NOTHING_TO_RETURN);
        }

        $this->model->safelyDBProcess(function () use ($products, $transactions) {
            DatabaseTransactionHelper::lockModel($this->model);

            $this->returnProducts($products);
            $this->refundTransactions($transactions);
        });
    }

    private function returnProducts(array $products): void
    {
        $saleProducts = $this->model->products()
            ->where('company_id', $this->model->company_id)
            ->whereIn('id', array_column($products, 'id'))
            ->get()
            ->keyBy('id');

        foreach ($products as $product) {
            $saleProduct = $saleProducts->get($product['id']);

            if ($saleProduct === null) {
                throw new SaleException(SaleException::PRODUCT_NOT_FOUND, [
                    'id' => $product['id'],
                ]);
            }

            $available = num_round($saleProduct->sold_quantity - $saleProduct->returned_quantity);

            if (num_gt($product['quantity'], $available)) {
                throw new SaleException(SaleException::RETURN_QUANTITY_EXCEEDS, [
                    'name' => $saleProduct->product_snapshot['name'],
                    'available' => $available,
                ]);
            }

            $saleProduct->increment('returned_quantity', $product['quantity']);

            StockProduct::query()
                ->where('company_id', $saleProduct->company_id)
                ->where('batch_id', $saleProduct->batch_id)
                ->where('product_id', $saleProduct->product_id)
                ->increment('quantity', $product['quantity']);
        }
    }

    private function refundTransactions(array $transactions): void
    {
        if (!$transactions) return;

        $amounts = $this->model->transactions()
            ->selectRaw('type, sum(amount) as amount')
            ->where('company_id', $this->model->company_id)
            ->groupBy('type')
            ->pluck('amount', 'type');

        $paid = $amounts->get(SaleTransactionTypeEnum::PAYMENT->value, 0);
        $refunded = $amounts->get(SaleTransactionTypeEnum::REFUND->value, 0);
        $available = num_round($paid - $refunded);
        $amount = array_sum(array_column($transactions, 'amount'));

        if (num_gt($amount, $available)) {
            throw new SaleException(SaleException::REFUND_AMOUNT_EXCEEDS, [
                'available' => $available,
            ]);
        }

        $this->createTransactions(SaleTransactionTypeEnum::REFUND, $transactions);

        $status = num_e($amount, $available)
            ? SaleStatusEnum::FULLY_CANCELLED->value
            : SaleStatusEnum::PARTIALLY_CANCELLED->value;

        $this->model->safelySave(['status' => $status]);
    }

    private function createTransactions(SaleTransactionTypeEnum $type, array $transactions): void
    {
        $rows = [];

        foreach ($transactions as $transaction) {
            $rows[] = [
                'company_id' => $this->model->company_id,
                'type' => $type->value,
                'payment_type' => $transaction['payment_type'],
                'amount' => $transaction['amount'],
            ];
        }

        $this->model->transactions()->createMany($rows);
    }
}
