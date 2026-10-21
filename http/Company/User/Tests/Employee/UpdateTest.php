<?php

namespace Http\Company\User\Tests\Employee;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '4',
            body: [
                'company_id' => 1,
                'username' => 'employee_1_1@local.host',
                'phone' => '998040000001',
                'password' => 'User_1234',
                'password_confirmation' => 'User_1234',

                'profile' => [
                    'first_name' => 'First name',
                    'last_name' => 'Last name',
                    'position' => 'Position',
                ],

                'roles' => [4, 5],
            ],
        );
    }
}
