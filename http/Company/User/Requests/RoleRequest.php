<?php

namespace Http\Company\User\Requests;

use Modules\User\Enums\UserTypeEnum;
use Modules\User\Requests\RoleRequest as BaseRoleRequest;

class RoleRequest extends BaseRoleRequest
{
    protected function additionalValidation(): void
    {
        $this->validateRoutes(
            prefix: 'company',
            user: user(),
        );
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        if (!$this->model->exists) {
            $this->validatedData['type'] = UserTypeEnum::COMPANY_EMPLOYEE->value;
        }
    }
}
