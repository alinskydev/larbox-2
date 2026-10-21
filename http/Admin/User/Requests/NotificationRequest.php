<?php

namespace Http\Admin\User\Requests;

use App\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Enums\NotificationTypeEnum;

class NotificationRequest extends FormRequest
{
    public function nonLocalizedRules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in([
                    NotificationTypeEnum::ANNOUNCEMENT->value,
                    NotificationTypeEnum::MESSAGE->value,
                ]),
            ],
            'text' => 'required|string|max:1000',

            'filter' => 'array|max:20',
            'filter.*' => 'required|string|max:255',

            'show' => 'array|max:20',
            'show.*' => 'required|string|max:255',
        ];
    }
}
