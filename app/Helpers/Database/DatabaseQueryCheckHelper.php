<?php

namespace App\Helpers\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Modules\User\Enums\UserTypeEnum;

class DatabaseQueryCheckHelper
{
    private static array $partitionKeys = [];

    public static function checkPartitions(QueryExecuted $query): void
    {
        if (!user() || !in_array(user()->type, [
            UserTypeEnum::COMPANY_DIRECTOR->value,
            UserTypeEnum::COMPANY_EMPLOYEE->value,
        ])) return;

        if (!preg_match('/^\s*(select|update|delete|with)\b/i', $query->sql)) return;

        $partitionKeys = static::partitionKeys($query->connection);

        foreach ($partitionKeys as $table => $keys) {
            foreach (static::relationNames($query->sql, $table) as $name) {
                foreach ($keys as $key) {
                    if (!static::hasCondition($query->sql, $name, $key)) {
                        $source = static::source();
                        $sql = $query->toRawSql();

                        throw new \Exception(implode(PHP_EOL, [
                            'Query on partitioned table has no partition key condition',
                            "Table: $table",
                            "Missing condition: $name.$key",
                            "Source: $source",
                            "SQL: $sql",
                        ]));
                    }
                }
            }
        }
    }

    private static function partitionKeys(Connection $connection): array
    {
        $connectionName = $connection->getName();

        if (isset(static::$partitionKeys[$connectionName])) {
            return static::$partitionKeys[$connectionName];
        }

        static::$partitionKeys[$connectionName] = [];

        $rows = $connection->select("
            SELECT c.relname AS table, pg_get_partkeydef(c.oid) AS definition
            FROM pg_partitioned_table p
            JOIN pg_class c ON c.oid = p.partrelid
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = current_schema()
        ");

        $partitionKeys = [];

        foreach ($rows as $row) {
            preg_match('/\((.+)\)/', $row->definition, $matches);
            $partitionKeys[$row->table] = array_map(fn($key) => trim($key, ' "'), explode(',', $matches[1]));
        }

        return static::$partitionKeys[$connectionName] = $partitionKeys;
    }

    private static function source(): string
    {
        $vendorPath = base_path('vendor');

        $frames = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 2);

        foreach ($frames as $frame) {
            $file = $frame['file'] ?? null;

            if ($file && !str_starts_with($file, $vendorPath)) {
                return str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file) . ':' . $frame['line'];
            }
        }

        return 'unknown';
    }

    private static function relationNames(string $sql, string $table): array
    {
        preg_match_all("/\b(?:from|join|update)\s+\"?$table\"?(?![\w\"])(?:\s+as\s+\"?(\w+)\"?)?/i", $sql, $matches);

        return array_unique(array_map(fn($alias) => $alias ?: $table, $matches[1]));
    }

    private static function hasCondition(string $sql, string $name, string $key): bool
    {
        $column = "(?:(?<!\w)\"?$name\"?\.|(?<![\.\w\"]))\"?$key\"?(?![\w\"])";
        $operator = '(?:=|<|>|\bin\b|\bbetween\b)';

        return (bool)preg_match("/$column\s*$operator|$operator\s*$column/i", $sql);
    }
}
