<?php

namespace Http\Company\Stock\Requests;

use App\Requests\ResourceFormRequest;
use App\Rules\UniqueRule;

class StockRequest extends ResourceFormRequest
{
    public function nonLocalizedRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueRule(
                    model: $this->model,
                    isFieldLocalized: false,
                ),
            ],
        ];
    }
}
