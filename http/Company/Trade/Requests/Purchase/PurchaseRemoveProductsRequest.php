<?php

namespace Http\Company\Trade\Requests\Purchase;

use App\Requests\ResourceFormRequest;
use Modules\Trade\Models\Purchase;

/**
 * @property Purchase $model
 */
class PurchaseRemoveProductsRequest extends ResourceFormRequest
{
    public function nonLocalizedRules(): array
    {
        return [
            'product_ids' => 'required|array|max:500',
            'product_ids.*' => 'required|integer|distinct',
        ];
    }
}
