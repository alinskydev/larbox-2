<?php

namespace Modules\History\Tests\StoreHttpLog;

use App\Testing\Domain\DomainTestCase;

class _TestCase extends DomainTestCase
{
    public int $userId = 1;

    protected function getValues(): array
    {
        return [];
    }
}
