<?php

namespace Http\Common\Auth\Tests\ResetPassword;

use Modules\User\Models\AuthCode;

class SendCodeTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'send-code',
            body: [
                'username' => 'director_1@local.host',
            ],
        );
    }

    public function test_error___active_code(): void
    {
        $username = 'director_1@local.host';

        (new AuthCode())->safelySave(['username' => $username]);

        $this->sendRequest(
            method: 'POST',
            path: 'send-code',
            body: [
                'username' => $username,
            ],
            assertStatus: 400,
        );
    }

    public function test_error___throttle(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'send-code',
            body: [
                'username' => 'unknown@local.host',
            ],
            throttleCount: 3,
        );
    }
}
