<?php

namespace App\Helpers\Database;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ! Partially blAIndly
 */
class DatabaseMigrationPartitionHelper
{
    public static function createTableWithPartitionsByMonths(
        string $table,
        string $fields,
        callable $tableCallback,
        bool $hasDefaultPartition = false,
        string $connection = 'main',
    ) {
        static::createTableWithPartitions(
            table: $table,
            tableCallback: $tableCallback,
            fields: $fields,
            hasDefaultPartition: $hasDefaultPartition,
            connection: $connection,
        );

        $db = DB::connection($connection);
        $partitionsCount = App::environment() === 'local' ? 1 : 10;

        for ($year = date('Y'); $year <= date('Y', strtotime("+$partitionsCount years")); $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $dateFrom = "$year-$month-01 00:00:00";
                $dateTo = date('Y-m-d 00:00:00', strtotime("$dateFrom +1 month"));

                $db->statement("
                    CREATE TABLE {$table}___{$year}_{$month} PARTITION OF $table
                    FOR VALUES FROM ('$dateFrom') TO ('$dateTo');
                ");
            }
        }
    }

    public static function createTableWithPartitionsByCompanyIdRanges(
        string $table,
        string $fields,
        callable $tableCallback,
        bool $hasDefaultPartition = false,
        string $connection = 'main',
    ) {
        static::createTableWithPartitions(
            table: $table,
            fields: $fields,
            tableCallback: $tableCallback,
            hasDefaultPartition: $hasDefaultPartition,
            connection: $connection,
        );

        $db = DB::connection($connection);
        $fieldsCount = count(explode(',', $fields));
        $partitionsCount = App::environment() === 'local' ? 1 : 5;

        for ($i = 0; $i < $partitionsCount; $i++) {
            $companyFrom = $i * 1000 + 1;
            $companyTo = ($i + 1) * 1000 + 1;

            $valuesFrom = implode(', ', array_fill(0, $fieldsCount, $companyFrom));
            $valuesTo = implode(', ', array_fill(0, $fieldsCount, $companyTo));

            $db->statement("
                CREATE TABLE {$table}___{$companyFrom}_{$companyTo} PARTITION OF $table
                FOR VALUES FROM ($valuesFrom) TO ($valuesTo);
            ");
        }
    }

    private static function createTableWithPartitions(
        string $table,
        string $fields,
        callable $tableCallback,
        bool $hasDefaultPartition,
        string $connection,
    ) {
        $db = DB::connection($connection);
        $schema = Schema::connection($connection);

        $schema->dropIfExists($table);
        $db->enableQueryLog();

        $tableCallback($table);

        $queries = $db->getRawQueryLog();
        $queries = Arr::pluck($queries, 'raw_query');

        $createTableQuery = array_shift($queries);
        $remainingQueries = $queries;

        $primaryKeys = null;

        // For simple PK

        if (preg_match('/"(\w+)"\s+[^,"]*primary key/i', $createTableQuery, $matches)) {
            $primaryKeys = [$matches[1]];
            $createTableQuery = str_replace('primary key', '', $createTableQuery);
        }

        // For composite PK

        foreach ($remainingQueries as $key => $query) {
            if (preg_match('/add\s+primary\s+key\s*\(([^)]+)\)/i', $query, $matches)) {
                $primaryKeys = array_map(
                    fn($column) => trim($column, " \t\n\r\0\x0B\""),
                    explode(',', $matches[1]),
                );

                unset($remainingQueries[$key]);
                break;
            }
        }

        if ($primaryKeys === null) {
            throw new \Exception("PK for {$table} not detected");
        }

        $partitionFields = array_map('trim', explode(',', $fields));
        $primaryKeys = array_values(array_unique(array_merge($primaryKeys, $partitionFields)));

        $createTableQuery = substr($createTableQuery, 0, strrpos($createTableQuery, ')'));
        $createTableQuery .= ', PRIMARY KEY (' . implode(', ', $primaryKeys) . ')) PARTITION BY RANGE (' . $fields . ')';

        $queries = array_merge([$createTableQuery], array_values($remainingQueries));

        $schema->dropIfExists($table);

        foreach ($queries as $query) {
            $db->statement($query);
        }

        $db->flushQueryLog();
        $db->disableQueryLog();

        if ($hasDefaultPartition) {
            $db->statement("CREATE TABLE {$table}___default PARTITION OF $table DEFAULT");
        }
    }
}
