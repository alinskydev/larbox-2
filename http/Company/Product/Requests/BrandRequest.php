<?php

namespace Http\Company\Product\Requests;

use App\Helpers\Validation\ValidationFileRulesHelper;
use App\Requests\ResourceFormRequest;
use App\Rules\UniqueRule;
use Modules\Seo\Traits\SeoMetaFormRequestTrait;

class BrandRequest extends ResourceFormRequest
{
    use SeoMetaFormRequestTrait;

    protected array $imageUploads = ([
        'image' => [
            'source' => 'external',
            'folder' => 'product_brand',
            'sizes' => [1920, 1080],
        ],
    ]);

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
            ...$this->deletableFileFieldsSingleValidation(
                field: 'image',
                rules: ValidationFileRulesHelper::image(),
            ),
        ];
    }
}
