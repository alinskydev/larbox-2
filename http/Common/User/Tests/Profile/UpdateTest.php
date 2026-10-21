<?php

namespace Http\Common\User\Tests\Profile;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            body: [
                'phone' => '998010000001',
                'password' => 'User_1234',
                'password_confirmation' => 'User_1234',

                'profile' => [
                    'first_name' => 'First name 1',
                    'last_name' => 'Last name 1',
                    'position' => 'Position 1',
                ],
            ],
        );
    }
}
