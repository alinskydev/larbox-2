<?php

namespace Http\Common\Auth\Requests\ResetPassword;

use App\Requests\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\User\Models\AuthCode;
use Modules\User\Models\User;

class CompleteRequest extends FormRequest
{
    protected array $rawFields = [
        'password',
        'password_confirmation',
    ];

    public function nonLocalizedRules(): array
    {
        return [
            'username' => 'required|email|max:255',
            'code' => 'required|string|max:255',
            'password' => [
                'required',
                Password::defaults(),
            ],
            'password_confirmation' => 'same:password',
        ];
    }

    protected function additionalValidation(): void
    {
        $user = User::query()->where('username', $this->username)->first();
        $authCode = AuthCode::query()
            ->where('username', $this->username)
            ->where('created_at', '>', now()->subMinutes(AuthCode::TTL_MINUTES)->format('Y-m-d H:i:s'))
            ->first();

        if (!$user || !$authCode) {
            $this->validator->errors()->add('username', __('validation.exists', [
                'attribute' => __('fields.username'),
            ]));

            return;
        }

        if (!$authCode->getService()->verifyCode($this->code)) {
            $this->validator->errors()->add('code', __('Неправильный код, осталось попыток: :attempts_left', [
                'attempts_left' => $authCode->attempts_left,
            ]));

            return;
        }

        setUser($user);
    }
}
