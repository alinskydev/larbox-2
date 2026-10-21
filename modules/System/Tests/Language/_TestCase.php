<?php

namespace Modules\System\Tests\Language;

use App\Testing\Domain\DomainTestCase;
use Modules\System\Models\Language;

class _TestCase extends DomainTestCase
{
    public int $userId = 1;

    protected function getValues(): array
    {
        return Language::query()
            ->get(['code', 'is_active'])
            ->pluck(
                fn($value) => (bool)$value->is_active,
                fn($key) => "language.$key->code.is_active",
            )
            ->toArray();
    }
}
