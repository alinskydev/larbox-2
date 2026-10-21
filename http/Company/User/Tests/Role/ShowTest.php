<?php

namespace Http\Company\User\Tests\Role;

class ShowTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: '4',
        );
    }
}
