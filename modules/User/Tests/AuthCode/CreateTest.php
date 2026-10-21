<?php

namespace Modules\User\Tests\AuthCode;

use Modules\User\Exceptions\AuthCodeException;
use Modules\User\Models\AuthCode;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->createAuthCode(),
            expected: [
                'auth_codes_count' => [0, 1],
            ],
        );
    }

    public function test_error___resend_not_available(): void
    {
        (new AuthCode())->safelySave(['username' => 'director_1@local.host']);

        $this->assertChanges(
            callback: fn() => $this->createAuthCode(),
            error: AuthCodeException::RESEND_NOT_AVAILABLE,
        );
    }

    private function createAuthCode(): void
    {
        (new AuthCode())->getService()->create('director_1@local.host');
    }
}
