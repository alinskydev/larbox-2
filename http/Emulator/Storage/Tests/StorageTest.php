<?php

namespace Http\Emulator\Storage\Tests;

class StorageTest extends _TestCase
{
    public function test_get_images(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'api/get/images',
            body: [
                'files' => [],
            ],
        );
    }

    public function test_upload(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'api/upload',
            body: [
                'files' => [],
            ],
        );
    }
}
