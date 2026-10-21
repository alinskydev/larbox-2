<?php

namespace Http\Admin\Company\Tests\Company;

class ResetDailyLimitTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '1/reset-daily-limit',
        );
    }
}
