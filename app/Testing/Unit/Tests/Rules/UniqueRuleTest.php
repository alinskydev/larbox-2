<?php

namespace App\Testing\Unit\Tests\Rules;

use App\Rules\UniqueRule;
use App\Testing\Unit\TestCase;
use Modules\Company\Models\Company;
use Modules\Product\Models\Option;

class UniqueRuleTest extends TestCase
{
    // Non localized

    public function test_non_localized_passed(): void
    {
        $rule = new UniqueRule(new Company(), false);
        $result = $rule->passes('name', 'Company 11');

        $this->assertTrue($result);
    }

    public function test_non_localized_failed(): void
    {
        $rule = new UniqueRule(new Company(), false);
        $result = $rule->passes('name', 'Company 1');

        $this->assertFalse($result);
    }

    // Localized

    public function test_localized_passed(): void
    {
        $rule = new UniqueRule(new Option(), true);
        $result = $rule->passes('name.en', 'Option 11 en');

        $this->assertTrue($result);
    }

    public function test_localized_failed(): void
    {
        $rule = new UniqueRule(new Option(), true);
        $result = $rule->passes('name.en', 'Option 1 en');

        $this->assertFalse($result);
    }
}
