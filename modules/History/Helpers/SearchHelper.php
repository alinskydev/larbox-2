<?php

namespace Modules\History\Helpers;

use Modules\History\Exceptions\HistoryException;

class SearchHelper
{
    public static function createdAtInterval(array $params): array
    {
        $createdAtFrom = $params['created_at'][0] ?? date('Y-m-d 00:00:00');
        $createdAtTo = $params['created_at'][1] ?? date('Y-m-d 23:59:59');
        $createdAtDiff = strtotime($createdAtTo) - strtotime($createdAtFrom);

        if ($createdAtDiff < 0 || $createdAtDiff > 60 * 60 * 24) {
            throw new HistoryException(HistoryException::MAX_INTERVAL_EXCEEDED);
        }

        return [
            date('Y-m-d H:i:s', strtotime($createdAtFrom)),
            date('Y-m-d H:i:s', strtotime($createdAtTo)),
        ];
    }
}
