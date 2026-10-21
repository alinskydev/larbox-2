<?php

namespace Http\Company\Trade\Requests\Purchase;

use App\Helpers\Validation\ValidationNumberRulesHelper;
use App\Requests\ResourceFormRequest;
use Illuminate\Support\Arr;
use Modules\Product\Models\Product;
use Modules\Trade\Models\Purchase;

/**
 * @property Purchase $model
 */
class PurchaseAddProductsRequest extends ResourceFormRequest
{
    public function nonLocalizedRules(): array
    {
        return [
            'products' => 'required|array|max:500',
            'products.*.product_id' => 'required|integer|distinct',
            'products.*.purchase_price' => ValidationNumberRulesHelper::amount(isRequired: true),
            'products.*.sale_price' => ValidationNumberRulesHelper::amount(isRequired: true),
            'products.*.quantity' => ValidationNumberRulesHelper::amount(isRequired: true),
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
}
