<?php

namespace Http\Admin\User\Tests\Role;

use Modules\User\Enums\UserTypeEnum;

class CreateTest extends _TestCase
{
    public function test_admin(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'type' => UserTypeEnum::ADMIN->value,
                'name' => 'Admin 2',
                'description' => 'Description 1-2',
                'routes' => [
                    'admin.company.company.index',
                    'admin.company.company.create',
                    'admin.company.company.update',
                ],
                'is_confirmed' => true,
            ],
        );
    }

    public function test_company_director(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'type' => UserTypeEnum::COMPANY_DIRECTOR->value,
                'name' => 'Director 2',
                'description' => 'Description 2-2',
                'routes' => [
                    'company.company.company.index',
                    'company.company.company.show',
                ],
                'is_confirmed' => true,
            ],
        );
    }

    public function test_error___invalid_routes(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'type' => UserTypeEnum::ADMIN->value,
                'name' => 'Admin 2',
                'description' => 'Description 1-2',
                'routes' => [
                    'company.company.company.index',
                    'company.company.company.show',
                ],
                'is_confirmed' => true,
            ],
            assertStatus: 422,
        );
    }
}
