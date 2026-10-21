<?php

namespace Http\Company\User\Tests\Role;

use Modules\User\Models\Role;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'name' => 'Employee 2',
                'description' => 'Description 3-2',
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
                'name' => 'Employee 2',
                'description' => 'Description 3-2',
                'routes' => [
                    'admin.company.company.index',
                    'admin.company.company.show',
                ],
                'is_confirmed' => true,
            ],
            assertStatus: 422,
        );
    }

    public function test_error___unavailable_routes(): void
    {
        Role::query()
            ->where('id', 2)
            ->update([
                'routes' => ['company.user.role.*'],
            ]);

        $this->sendRequest(
            method: 'POST',
            body: [
                'name' => 'Employee 2',
                'description' => 'Description 3-2',
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
