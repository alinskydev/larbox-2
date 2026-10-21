<?php

namespace Http\Common\User\Tests\Notification;

class MarkAllSeenTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: 'mark-all-seen',
        );
    }
}
