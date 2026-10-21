<?php

namespace Http\Common\Auth\Tests;

class LoginTest extends _TestCase
{
    public function test_as_director(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'login',
            body: [
                'username' => 'director_1@local.host',
                'password' => 'User_1234',
            ],
        );
    }

    public function test_as_employee(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'login',
            body: [
                'username' => 'employee_1@local.host',
                'password' => 'User_1234',
            ],
        );
    }
}
