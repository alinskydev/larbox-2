<?php

namespace Modules\User\Tests\AuthCode;

use App\Testing\Domain\DomainTestCase;
use Modules\User\Models\AuthCode;

class _TestCase extends DomainTestCase
{
    protected function getValues(): array
    {
        return [
            'auth_codes_count' => AuthCode::query()->count(),
        ];
    }
}
