<?php

namespace Http\Company\Product\Requests;

use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use App\Rules\UniqueRule;
use Modules\Product\Models\Specification;

class OptionRequest extends ResourceFormRequest
{
    public function nonLocalizedRules(): array
    {
        return [
            'specification_id' => [
                'required',
                'integer',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Specification::query(),
                ),
            ],
        ];
    }

    public function localizedRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueRule(
                    model: $this->model,
                    isFieldLocalized: true,
                    additionalQuery: function ($query) {
                        $query->where('specification_id', $this->specification_id);
                    },
                ),
            ],
        ];
    }
}
