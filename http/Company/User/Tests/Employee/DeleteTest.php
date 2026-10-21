<?php

namespace Http\Company\User\Tests\Employee;

use Modules\User\Models\User;

class DeleteTest extends _TestCase
{
    public function test_delete(): void
    {
        $this->sendRequest(
            method: 'DELETE',
            path: '4',
        );
    }

    public function test_restore(): void
    {
        User::query()->where('id', 4)->delete();

        $this->sendRequest(
            method: 'DELETE',
            path: '4/restore',
        );
    }

    public function test_error___self_delete(): void
    {
        $this->authAsUser(4);

        user()->roles()->update([
            'routes' => ['company.*'],
        ]);

        $this->sendRequest(
            method: 'DELETE',
            path: '4',
            assertStatus: 400,
        );
    }
}
