<?php

namespace Http\Admin\History\Tests\StoreHttpLog;

use App\Testing\Feature\PostmanTestCase;

class _TestCase extends PostmanTestCase
{
    public int $userId = 1;

    public string $requestUrl = 'admin/history/store-http-log';
}
