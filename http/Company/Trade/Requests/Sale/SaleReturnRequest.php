<?php

namespace Http\Company\Trade\Requests\Sale;

use App\Helpers\Validation\ValidationNumberRulesHelper;
use App\Requests\ResourceFormRequest;
use Illuminate\Validation\Rule;
use Modules\Trade\Enums\SaleTransactionPaymentTypeEnum;
use Modules\Trade\Models\Sale;

/**
 * @property Sale $model
 */
class SaleReturnRequest extends ResourceFormRequest
{
    protected array $defaults = [
        'products' => [],
        'transactions' => [],
    ];

    public function nonLocalizedRules(): array
    {
        return [
            'products' => 'required_without:transactions|array|max:500',
            'products.*.id' => 'required|integer|distinct',
            'products.*.quantity' => ValidationNumberRulesHelper::amount(isRequired: true),

            'transactions' => 'required_without:products|array|max:10',
            'transactions.*.payment_type' => [
                'required',
                Rule::in(SaleTransactionPaymentTypeEnum::values()),
            ],
            'transactions.*.amount' => ValidationNumberRulesHelper::amount(isRequired: true),
        ];
    }
}
