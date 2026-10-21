<?php

namespace Http\Emulator\Store\Tests;

class AuthTest extends _TestCase
{
    public function test_login(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'auth/login',
        );
    }
}
