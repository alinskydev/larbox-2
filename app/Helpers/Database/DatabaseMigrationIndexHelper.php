<?php

namespace App\Helpers\Database;

use Illuminate\Support\Facades\DB;

class DatabaseMigrationIndexHelper
{
    public static function createLowerIndex(string $table, string $field, ?string $scopeField = null): void
    {
        $name = "{$table}_{$field}_lower_index";
        $columns = array_filter([$scopeField, "LOWER($field)"]);
        $columns = implode(', ', $columns);

        DB::statement("CREATE INDEX $name ON \"$table\" ($columns)");
    }

    public static function createGinIndex(string $table, string $field): void
    {
        $name = "{$table}_{$field}_gin_index";
        DB::statement("CREATE INDEX $name ON \"$table\" USING gin ($field gin_trgm_ops)");
    }

    public static function createLocalizedLowerIndex(string $table, string $field, ?string $scopeField = null): void
    {
        $languages = array_keys(config('larbox.languages'));

        foreach ($languages as $language) {
            $name = "{$table}_{$field}_localized_{$language}_lower_index";
            $columns = array_filter([$scopeField, "LOWER($field->>'$language')"]);
            $columns = implode(', ', $columns);

            DB::statement("CREATE INDEX $name ON \"$table\" ($columns)");
        }
    }

    public static function createLocalizedGinIndex(string $table, string $field): void
    {
        $languages = array_keys(config('larbox.languages'));

        foreach ($languages as $language) {
            $name = "{$table}_{$field}_localized_{$language}_gin_index";
            DB::statement("CREATE INDEX $name ON \"$table\" USING gin (($field->>'$language') gin_trgm_ops)");
        }
    }
}
