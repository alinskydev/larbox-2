<?php

namespace Http\Company\User\Tests\Role;

class DeleteTest extends _TestCase
{
    public function test_delete(): void
    {
        $this->sendRequest(
            method: 'DELETE',
            path: '4',
        );
    }
}
