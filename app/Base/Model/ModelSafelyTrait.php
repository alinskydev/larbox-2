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
        if (DB::transactionLevel() > 0) {
            $callback();
            return;
        }

        DB::beginTransaction();

        foreach ($this->additionalConnections as $connection) {
            DB::connection($connection)->beginTransaction();
        }

        try {
            $callback();
            DB::commit();

            foreach ($this->additionalConnections as $connection) {
                DB::connection($connection)->commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($this->additionalConnections as $connection) {
                DB::connection($connection)->rollBack();
            }

            throw $e;
        }
    }
}
