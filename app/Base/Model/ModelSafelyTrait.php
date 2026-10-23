<?php

namespace App\Base\Model;

use App\Base\Model;
use Illuminate\Support\Facades\DB;

/**
 * @mixin Model
 * @mixin ModelSoftDeleteTrait
 */
trait ModelSafelyTrait
{
    public function safelySave(array $attributes = []): void
    {
        $this->safelyDBProcess(function () use ($attributes) {
            $this->fill($attributes);
            $this->usesTimestamps() ? $this->touch() : $this->save();
        });
    }

    public function safelyDelete(): void
    {
        $this->safelyDBProcess(function () {
            $this->delete();
        });
    }

    public function safelyRestore(): void
    {
        $this->safelyDBProcess(function () {
            $this->restore();
        });
    }

    public function safelyDBProcess(callable $callback): void
    {
        $connections = array_filter(
            [DB::getDefaultConnection(), ...$this->additionalConnections],
            fn($connection) => DB::connection($connection)->transactionLevel() === 0,
        );

        if (!$connections) {
            $callback();
            return;
        }

        foreach ($connections as $connection) {
            DB::connection($connection)->beginTransaction();
        }

        try {
            $callback();

            // Main is committed last: it is rolled back if an additional connection fails to commit
            foreach (array_reverse($connections) as $connection) {
                DB::connection($connection)->commit();
            }
        } catch (\Throwable $e) {
            foreach ($connections as $connection) {
                DB::connection($connection)->rollBack();
            }

            throw $e;
        }
    }
}
