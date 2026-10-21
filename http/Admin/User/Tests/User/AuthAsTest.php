<?php

namespace Http\Admin\User\Tests\User;

class AuthAsTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '2/auth-as',
        );
    }
}
