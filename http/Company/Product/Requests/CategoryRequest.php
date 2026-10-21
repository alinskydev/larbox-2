<?php

namespace Http\Company\Product\Requests;

use App\Requests\ResourceFormRequest;
use App\Rules\UniqueRule;
use Modules\Seo\Traits\SeoMetaFormRequestTrait;

class CategoryRequest extends ResourceFormRequest
{
    use SeoMetaFormRequestTrait;

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
        ];
    }
}
