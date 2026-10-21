<?php

namespace App\Database;

use Illuminate\Database\Schema\PostgresBuilder;
use Illuminate\Support\Facades\Cache;

class PostgresSchemaBuilder extends PostgresBuilder
{
    public function getColumnListing($table)
    {
        return Cache::tags('db_schema')->remember("db_schema:$table", 86400, fn() => parent::getColumnListing($table));
    }
}
