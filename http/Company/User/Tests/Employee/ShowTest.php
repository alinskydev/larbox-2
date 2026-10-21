<?php

namespace Http\Company\User\Tests\Employee;

class ShowTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: '4',
        );
    }
}
