<?php

namespace Http\Company\Product\Requests;

use App\Requests\ResourceFormRequest;
use App\Rules\UniqueRule;

class SpecificationRequest extends ResourceFormRequest
{
    public function nonLocalizedRules(): array
    {
        return [
            'options' => 'array|max:20',
            'options.*.id' => 'integer',
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
                ),
            ],
            'description' => 'nullable|string',

            'options.*.name' => 'required|distinct|string|max:255',
        ];
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        $this->model->fillRelations(
            oneToMany: [
                'options' => $this->validatedData['options'] ?? [],
            ],
        );
    }
}
