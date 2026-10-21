<?php

namespace Http\Admin\Company\Requests;

use App\Requests\ResourceFormRequest;
use App\Rules\UniqueRule;
use Modules\Company\Enums\SubscriptionRecordLimitEnum;

class SubscriptionRequest extends ResourceFormRequest
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
            'description' => 'required|string|max:1000',

            ...array_fill_keys(
                array_map(fn($value) => "record_limits.$value", SubscriptionRecordLimitEnum::values()),
                'required|integer|min:0',
            ),
        ];
    }
}
