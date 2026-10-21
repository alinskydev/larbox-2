<?php

namespace App\Helpers\Database;

use App\Base\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DatabaseTransactionHelper
{
    private static array $locks = [];

    public static function lock(string $key, int $seconds = 60): void
    {
        if (isset(static::$locks[$key])) return;

        $lock = Cache::lock($key, $seconds);
        $lock->block($seconds);

        static::$locks[$key] = $lock;

        $release = function () use ($key) {
            if (isset(static::$locks[$key])) {
                static::$locks[$key]->release();
                unset(static::$locks[$key]);
            }
        };

        DB::afterCommit($release);
        DB::afterRollBack($release);
    }

    public static function lockModel(Model $model): void
    {
        $table = $model->getTable();
        $pk = $model->getKey();

        static::lock("$table:$pk");
    }
}
