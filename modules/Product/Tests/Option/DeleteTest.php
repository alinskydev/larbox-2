<?php

namespace Modules\Product\Tests\Option;

use Modules\Product\Exceptions\OptionException;

class DeleteTest extends _TestCase
{
    public function test_success(): void
    {
        $this->getOption(2)->products()->detach();

        $this->assertChanges(
            callback: fn() => $this->getOption(2)->safelyDelete(),
            expected: [
                'options_count' => [2, 1],
            ],
        );
    }

    public function test_error___attached_to_products(): void
    {
        $this->assertChanges(
            callback: fn() => $this->getOption(1)->safelyDelete(),
            error: OptionException::ATTACHED_TO_PRODUCTS,
        );
    }
}
