<?php

namespace Http\Common\Auth\Tests;

class LogoutTest extends _TestCase
{
    public int $userId = 2;

    public function test_success(): void
    {
        $token = user()->createToken('test')->accessToken;
        user()->withAccessToken($token);

        $this->sendRequest(
            method: 'POST',
            path: 'logout',
        );
    }
}
