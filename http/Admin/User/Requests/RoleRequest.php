<?php

namespace Http\Admin\User\Requests;

use Illuminate\Validation\Rule;
use Modules\User\Enums\UserTypeEnum;
use Modules\User\Requests\RoleRequest as BaseRoleRequest;

class RoleRequest extends BaseRoleRequest
{
    public function nonLocalizedRules(): array
    {
        return [
            ...parent::nonLocalizedRules(),
            'type' => [
                Rule::requiredIf(!$this->model->exists),
                Rule::in([
                    UserTypeEnum::ADMIN->value,
                    UserTypeEnum::COMPANY_DIRECTOR->value,
                ]),
            ],
        ];
    }

    protected function additionalValidation(): void
    {
        $this->validateRoutes(
            prefix: match ($this->validated('type')) {
                UserTypeEnum::ADMIN->value => 'admin',
                UserTypeEnum::COMPANY_DIRECTOR->value => 'company',
            },
        );
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'type' => $this->model->type ?? $this->type,
        ]);
    }
}
