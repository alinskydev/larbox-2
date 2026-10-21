<?php

namespace Http\Admin\System\Requests;

use App\Helpers\Validation\ValidationFileRulesHelper;
use App\Requests\FormRequest;

class SettingRequest extends FormRequest
{
    protected array $fileUploads = [
        'favicon' => [
            'source' => 'local',
            'folder' => 'images',
        ],
        'logo' => [
            'source' => 'local',
            'folder' => 'images',
        ],
    ];

    public function nonLocalizedRules(): array
    {
        return [
            'admin_email' => 'required|email|max:255',
            'favicon' => ValidationFileRulesHelper::image(),
            'logo' => ValidationFileRulesHelper::image(),
            'project_name' => 'required|string|max:255',
        ];
    }
}
