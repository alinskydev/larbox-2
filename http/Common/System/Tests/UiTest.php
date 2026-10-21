<?php

namespace Http\Common\System\Tests;

class UiTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(path: 'ui');
    }

    public function test_success___company_director(): void
    {
        $this->authAsUser(2);

        $this->sendRequest(path: 'ui');
    }

    public function test_success___company_employee(): void
    {
        $this->authAsUser(4);

        $this->sendRequest(path: 'ui');
    }
}
