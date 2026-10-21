<?php

namespace Http\Admin\User\Tests\User;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '4',
            body: [
                'username' => 'employee_1@local.host',
                'phone' => '998040000001',
                'password' => 'User_1234',
                'password_confirmation' => 'User_1234',

                'profile' => [
                    'first_name' => 'First name',
                    'last_name' => 'Last name',
                    'position' => 'Position',
                ],

                'roles' => [4, 5],
                'responsible_companies' => [1, 2],
            ],
        );
    }
}
