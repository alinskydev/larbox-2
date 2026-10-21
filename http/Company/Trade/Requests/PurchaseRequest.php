<?php

namespace Http\Company\Trade\Requests;

use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use Modules\Stock\Models\Stock;

class PurchaseRequest extends ResourceFormRequest
{
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
        ];
    }
}
