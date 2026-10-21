<?php

namespace Http\Common\User\Requests;

use App\Requests\ResourceFormRequest;
use App\Rules\PhoneRule;
use Illuminate\Validation\Rules\Password;

class ProfileRequest extends ResourceFormRequest
{
    protected array $rawFields = [
        'password',
        'password_confirmation',
    ];

    public function __construct()
    {
        if ($model = user()) {
            $this->model = $model;
        }

        parent::__construct();
    }

    public function nonLocalizedRules(): array
    {
        return [
            'phone' => [
                'sometimes',
                'required',
                new PhoneRule(),
            ],
            'password' => [
                'nullable',
                Password::defaults(),
            ],
            'password_confirmation' => [
                'required_with:password',
                'same:password',
            ],

            'profile.first_name' => 'required|string|max:255',
            'profile.last_name' => 'required|string|max:255',
            'profile.position' => 'nullable|string|max:255',
        ];
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        $this->model->fillRelations(
            oneToOne: [
                'profile' => $this->validatedData['profile'] ?? [],
            ],
        );
    }
}
