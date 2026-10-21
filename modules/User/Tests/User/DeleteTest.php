<?php

namespace Modules\User\Tests\User;

use Modules\User\Exceptions\UserException;

class DeleteTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getUser(4)->safelyDelete(),
            expected: [
                'users_count' => [4, 3],
            ],
        );
    }

    public function test_error___self_deletion(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getUser(2)->safelyDelete(),
            error: UserException::SELF_DELETION,
        );
    }

    public function test_error___undeletable(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getUser(1)->safelyDelete(),
            error: UserException::UNDELETABLE,
        );
    }
}
