<?php

namespace Http\Company\User\Tests\Role;

class AvailableRoutesTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: 'available-routes',
        );
    }
}
