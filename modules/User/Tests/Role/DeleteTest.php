<?php

namespace Modules\User\Tests\Role;

use Modules\User\Exceptions\RoleException;

class DeleteTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getRole(5)->safelyDelete(),
            expected: [
                'roles_count' => [5, 4],
            ],
        );
    }

    public function test_error___attached_to_users(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getRole(2)->safelyDelete(),
            error: RoleException::ATTACHED_TO_USERS,
        );
    }

    public function test_error___undeletable(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getRole(1)->safelyDelete(),
            error: RoleException::UNDELETABLE,
        );
    }
}
