<?php

namespace Http\Common\Auth\Requests\ResetPassword;

use App\Requests\FormRequest;

class SendCodeRequest extends FormRequest
{
    public function nonLocalizedRules(): array
    {
        return [
            'username' => 'required|email|max:255',
        ];
    }
}
