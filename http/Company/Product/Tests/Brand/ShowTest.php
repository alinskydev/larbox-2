<?php

namespace Http\Company\Product\Tests\Brand;

use Modules\Product\Models\Brand;

class ShowTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            path: '1',
        );
    }

    public function test_success___same_creator(): void
    {
        Brand::query()->where('id', 1)->update(['creator_id' => 2]);

        user()->roles()->update([
            'routes' => [
                'company.product.brand.show',
            ],
        ]);

        $this->sendRequest(
            path: '1',
        );
    }

    public function test_error___other_creator(): void
    {
        Brand::query()->where('id', 1)->update(['creator_id' => 1]);

        user()->roles()->update([
            'routes' => [
                'company.product.brand.show',
            ],
        ]);

        $this->sendRequest(
            path: '1',
            assertStatus: 400,
        );
    }
}
