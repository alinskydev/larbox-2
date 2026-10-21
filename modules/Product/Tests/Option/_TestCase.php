<?php

namespace Modules\Product\Tests\Option;

use App\Testing\Domain\DomainTestCase;
use Modules\Product\Models\Option;

class _TestCase extends DomainTestCase
{
    public int $userId = 2;

    protected function getValues(): array
    {
        return [
            'options_count' => Option::query()->count(),
        ];
    }

    protected function getOption(int $id): Option
    {
        return Option::query()->findOrFail($id);
    }
}
