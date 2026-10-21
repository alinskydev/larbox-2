<?php

namespace App\Testing\Unit\Tests\Rules;

use App\Rules\PhoneRule;
use App\Testing\Unit\TestCase;

class PhoneRuleTest extends TestCase
{
    public function test_passed(): void
    {
        $rule = new PhoneRule();
        $result = $rule->passes('phone', '998000000001');

        $this->assertTrue($result);
    }

    public function test_failed_repeated_prefix(): void
    {
        $rule = new PhoneRule();
        $result = $rule->passes('phone', '998998000000001');

        $this->assertFalse($result);
    }

    public function test_failed_spaces(): void
    {
        $rule = new PhoneRule();
        $result = $rule->passes('phone', '998 00 000 00 01');

        $this->assertFalse($result);
    }

    public function test_failed_plus(): void
    {
        $rule = new PhoneRule();
        $result = $rule->passes('phone', '+998000000001');

        $this->assertFalse($result);
    }
}
