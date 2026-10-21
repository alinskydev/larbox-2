<?php

namespace Http\Company\Audit\Tests\RecordAction;

use App\Testing\Feature\PostmanTestCase;

class _TestCase extends PostmanTestCase
{
    public int $userId = 2;

    public string $requestUrl = 'company/audit/record-action';
}
