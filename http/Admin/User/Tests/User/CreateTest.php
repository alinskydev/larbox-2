<?php

namespace Http\Admin\User\Tests\User;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                'username' => 'admin_2@local.host',
                'phone' => '998010000002',
                'password' => 'User_1234',
                'password_confirmation' => 'User_1234',

                'profile' => [
                    'first_name' => 'First name',
                    'last_name' => 'Last name',
                    'position' => 'Position',
                ],

                'roles' => [1],
                'responsible_companies' => [1, 2],
            ],
        );
    }
}
