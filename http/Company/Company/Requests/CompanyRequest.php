<?php

namespace Http\Company\Company\Requests;

use App\Helpers\Validation\ValidationFileRulesHelper;
use App\Requests\ResourceFormRequest;

class CompanyRequest extends ResourceFormRequest
{
    protected array $fileUploads = [
        'logo' => [
            'source' => 'local',
            'folder' => 'images',
        ],
    ];

    public function nonLocalizedRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            ...$this->deletableFileFieldsSingleValidation(
                field: 'logo',
                rules: ValidationFileRulesHelper::image(),
            ),
            'address' => 'required|string|max:255',

            'telegram_chats' => 'array|max:5',
            'telegram_chats.*.name' => 'required|string|max:255',
            'telegram_chats.*.chat_id' => 'required|integer',
        ];
    }
}
