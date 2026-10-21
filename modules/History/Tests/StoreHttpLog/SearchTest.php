<?php

namespace Modules\History\Tests\StoreHttpLog;

use Modules\History\Exceptions\HistoryException;
use Modules\History\Helpers\SearchHelper;

class SearchTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->createdAtInterval(to: '2026-01-01 23:59:59'),
        );
    }

    public function test_error___max_interval_exceeded(): void
    {
        $this->assertChanges(
            callback: fn() => $this->createdAtInterval(to: '2026-01-02 00:00:01'),
            error: HistoryException::MAX_INTERVAL_EXCEEDED,
        );
    }

    private function createdAtInterval(string $to): void
    {
        $params = [
            'created_at' => ['2026-01-01 00:00:00', $to],
        ];

        SearchHelper::createdAtInterval($params);
    }
}
