<?php

namespace Http\Company\Trade\Requests;

use App\Helpers\Validation\ValidationNumberRulesHelper;
use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Modules\Product\Models\Product;
use Modules\Stock\Models\Stock;
use Modules\Trade\Enums\SaleStatusEnum;
use Modules\Trade\Enums\SaleTransactionPaymentTypeEnum;

class SaleRequest extends ResourceFormRequest
{
    protected array $defaults = [
        'status' => SaleStatusEnum::COMPLETED->value,
    ];

    public function nonLocalizedRules(): array
    {
        return [
            'stock_id' => [
                'required',
                'integer',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Stock::query()->filterByNestedCompanies(),
                ),
            ],

            'products' => 'required|array|max:500',
            'products.*.product_id' => 'required|integer|distinct',
            'products.*.quantity' => ValidationNumberRulesHelper::amount(isRequired: true),

            'transactions' => 'required|array|max:10',
            'transactions.*.payment_type' => [
                'required',
                Rule::in(SaleTransactionPaymentTypeEnum::values()),
            ],
            'transactions.*.amount' => ValidationNumberRulesHelper::amount(isRequired: true),
        ];
    }

    protected function additionalValidation(): void
    {
        $products = $this->validated('products');
        $productIds = Arr::pluck($products, 'product_id');
        $existingIds = Product::query()->whereIn('id', $productIds)->pluck('id');

        foreach ($products as $index => $product) {
            if (!$existingIds->contains($product['product_id'])) {
                $this->validator->errors()->add(
                    "products.$index.product_id",
                    __('Выбранная запись не существует или была удалена'),
                );

                return;
            }
        }
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        $this->model->saleProducts = $this->validatedData['products'];
        $this->model->salePayments = $this->validatedData['transactions'];
    }
}
