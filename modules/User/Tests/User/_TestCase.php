<?php

namespace Modules\User\Tests\User;

use App\Testing\Domain\DomainTestCase;
use Modules\User\Models\User;

class _TestCase extends DomainTestCase
{
    public int $userId = 2;

    protected function getValues(): array
    {
        return [
            'users_count' => User::query()->count(),
        ];
    }

    protected function getUser(int $id): User
    {
        return User::query()->findOrFail($id);
    }
}
