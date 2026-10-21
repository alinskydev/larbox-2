<?php

namespace Modules\Company\Tests\Company;

use Modules\Company\Exceptions\CompanyException;
use Modules\Company\Models\Company;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->createCompany(),
            expected: [
                'companies_count' => [2, 3],
            ],
        );
    }

    public function test_error___companies_limit_reached(): void
    {
        Company::query()->insert([
            'id' => 5000,
            'creator_id' => 1,
            'subscription_id' => 1,
            'name' => 'Company 5000',
            'address' => 'Address 5000',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        $this->assertChanges(
            callback: fn() => $this->createCompany(),
            error: CompanyException::COMPANIES_LIMIT_REACHED,
        );
    }

    private function createCompany(): void
    {
        (new Company())->safelySave([
            'subscription_id' => 1,
            'name' => 'Company 3',
            'address' => 'Address 3',
        ]);
    }
}
