<?php

namespace Http\Company\Trade\Requests;

use App\Helpers\Validation\ValidationNumberRulesHelper;
use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use Modules\Product\Models\Product;

class RecalculationProductRequest extends ResourceFormRequest
{
    public function nonLocalizedRules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Product::query()->filterByNestedCompanies(),
                ),
            ],
            'price' => ValidationNumberRulesHelper::amount(isRequired: true),
        ];
    }
}
