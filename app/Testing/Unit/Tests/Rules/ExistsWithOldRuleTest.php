<?php

namespace App\Testing\Unit\Tests\Rules;

use App\Rules\ExistsWithOldRule;
use App\Testing\Unit\TestCase;
use Modules\Company\Models\Company;
use Modules\Product\Models\Option;
use Modules\Product\Models\Product;

class ExistsWithOldRuleTest extends TestCase
{
    // Single

    public function test_single_passed_new(): void
    {
        $rule = new ExistsWithOldRule(new Product(), Company::query());
        $result = $rule->passes('company_id', 1);

        $this->assertTrue($result);
    }

    public function test_single_passed_exists(): void
    {
        Company::query()->where('id', 1)->delete();

        $rule = new ExistsWithOldRule(Product::query()->findOrFail(1), Company::query());
        $result = $rule->passes('company_id', 1);

        $this->assertTrue($result);

        Company::query()->where('id', 1)->restore();
    }

    public function test_single_failed(): void
    {
        $rule = new ExistsWithOldRule(new Product(), Company::query());
        $result = $rule->passes('company_id', 11);

        $this->assertFalse($result);
    }

    // Multiple

    public function test_multiple_passed_new(): void
    {
        setUser(2);

        $rule = new ExistsWithOldRule(new Product(), Option::query());
        $result = $rule->passes('options', [1, 2]);

        $this->assertTrue($result);
    }

    public function test_multiple_failed(): void
    {
        setUser(2);

        $rule = new ExistsWithOldRule(new Product(), Option::query());
        $result = $rule->passes('options', [1, 11]);

        $this->assertFalse($result);
    }
}
