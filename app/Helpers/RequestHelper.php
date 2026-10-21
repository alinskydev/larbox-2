<?php

namespace App\Helpers;

class RequestHelper
{
    public static function storageHttpHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Storage-Secret-Key' => config('integrations.storage.secret_key'),
        ];
    }

    public static function pageSize(int $limit = 20): string
    {
        $pageSize = request()->query('page_size', 20);
        $pageSize = $pageSize >= 1 && $pageSize <= $limit ? $pageSize : $limit;

        return $pageSize;
    }

    public static function page(int $limit = 300): int
    {
        $page = request()->query('page', 1);
        $page = $page >= 1 && $page <= $limit ? $page : $limit;

        return $page;
    }

    public static function abortUnlessTesting(): void
    {
        if (app()->environment() !== 'testing') {
            throw new \Exception('Not working');
        }
    }
}
