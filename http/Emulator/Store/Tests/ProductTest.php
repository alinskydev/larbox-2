<?php

namespace Http\Emulator\Store\Tests;

class ProductTest extends _TestCase
{
    public function test_list(): void
    {
        $this->sendRequest(
            method: 'GET',
            path: 'product/list',
        );
    }

    public function test_create(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'product/create',
        );
    }

    public function test_update(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'product/update/1',
        );
    }

    public function test_delete(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'product/delete/1',
        );
    }
}
