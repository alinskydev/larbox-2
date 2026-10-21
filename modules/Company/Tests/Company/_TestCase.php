<?php

namespace Modules\Company\Tests\Company;

use App\Testing\Domain\DomainTestCase;
use Modules\Company\Models\Company;

class _TestCase extends DomainTestCase
{
    public int $userId = 1;

    protected function getValues(): array
    {
        return [
            'companies_count' => Company::query()->count(),
        ];
    }
}
