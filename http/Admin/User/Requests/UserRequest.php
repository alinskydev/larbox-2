<?php

namespace Http\Admin\User\Requests;

use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use App\Rules\PhoneRule;
use App\Rules\UniqueRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Company\Models\Company;
use Modules\User\Enums\UserTypeEnum;
use Modules\User\Exceptions\UserException;
use Modules\User\Models\Role;
use Modules\User\Models\User;

/**
 * @property User $model
 */
class UserRequest extends ResourceFormRequest
{
    protected array $rawFields = [
        'password',
        'password_confirmation',
    ];

    public function __construct()
    {
        parent::__construct();

        if ($this->model->exists) {
            if ($this->model->id === user()->id) {
                throw new UserException(UserException::UPDATING_FORBIDDEN);
            }
        } else {
            $this->model->type = UserTypeEnum::ADMIN->value;
        }
    }

    public function nonLocalizedRules(): array
    {
        return [
            'username' => [
                'required',
                'email',
                'max:255',
                new UniqueRule(
                    model: $this->model,
                    isFieldLocalized: false,
                    shouldShowDetailedErrorMessage: false,
                ),
            ],
            'phone' => [
                'required',
                new PhoneRule(),
            ],
            'password' => [
                Rule::requiredIf(!$this->model->exists),
                Password::defaults(),
            ],
            'password_confirmation' => [
                'required_with:password',
                'same:password',
            ],

            'profile.first_name' => 'nullable|string|max:255',
            'profile.last_name' => 'nullable|string|max:255',
            'profile.position' => 'nullable|string|max:255',

            'roles' => [
                'required',
                'array',
                'max:10',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Role::query()->where('type', $this->model->type),
                ),
            ],
            'roles.*' => 'required|integer|distinct',

            'responsible_companies' => [
                'array',
                'max:100',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Company::query(),
                ),
            ],
            'responsible_companies.*' => 'required|integer|distinct',
        ];
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        if ($this->model->getService()->isSuperadmin()) {
            Arr::forget($this->validatedData, ['password', 'roles']);
        }

        $this->model->fillRelations(
            oneToOne: [
                'profile' => $this->validatedData['profile'] ?? [],
            ],
        );

        if ($this->model->type === UserTypeEnum::ADMIN->value && !$this->model->getService()->isSuperadmin()) {
            $this->model->fillRelations(
                manyToMany: [
                    'roles' => $this->validatedData['roles'] ?? [],
                    'responsible_companies' => $this->validatedData['responsible_companies'] ?? [],
                ],
            );
        }
    }
}
