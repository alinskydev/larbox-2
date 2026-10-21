<?php

namespace Http\Common\Auth\Requests\Auth;

use App\Requests\FormRequest;
use Illuminate\Support\Facades\Auth;

class LoginRequest extends FormRequest
{
    protected array $rawFields = [
        'password',
    ];

    public function nonLocalizedRules(): array
    {
        return [
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ];
    }


    protected function additionalValidation(): void
    {
        $credentials = $this->only('username', 'password');

        if (!Auth::attempt($credentials)) {
            $this->validator->errors()->add('password', __('Неправильный :field', [
                'field' => __('fields.password'),
            ]));

            return;
        }

        $company = user()->company;

        if ($company && $company->deleted_at) {
            $this->validator->errors()->add('password', __('Ваша компания отключена'));
            return;
        }
    }
}
