<?php

namespace Http\Admin\System\Requests;

use App\Helpers\Validation\ValidationFileRulesHelper;
use App\Requests\ResourceFormRequest;
use App\Rules\UniqueRule;

class LanguageRequest extends ResourceFormRequest
{
    protected array $fileUploads = [
        'image' => [
            'source' => 'local',
            'folder' => 'images',
        ],
    ];

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
            'image' => ValidationFileRulesHelper::image(),
        ];
    }
}
