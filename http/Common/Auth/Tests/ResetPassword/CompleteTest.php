<?php

namespace Http\Common\Auth\Tests\ResetPassword;

use Modules\User\Models\AuthCode;

class CompleteTest extends _TestCase
{
    public function test_success(): void
    {
        $username = 'director_1@local.host';

        (new AuthCode())->safelySave(['username' => $username]);

        $this->sendRequest(
            method: 'POST',
            path: 'complete',
            body: [
                'username' => $username,
                'code' => '123456',
                'password' => 'User_1234',
                'password_confirmation' => 'User_1234',
            ],
        );
    }
}
