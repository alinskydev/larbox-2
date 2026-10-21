<?php

namespace Modules\User\Tests\Role;

use App\Testing\Domain\DomainTestCase;
use Modules\User\Models\Role;

class _TestCase extends DomainTestCase
{
    public int $userId = 1;

    protected function getValues(): array
    {
        return [
            'roles_count' => Role::query()->count(),
        ];
    }

    protected function getRole(int $id): Role
    {
        return Role::query()->findOrFail($id);
    }
}
