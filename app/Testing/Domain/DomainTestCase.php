<?php

namespace App\Testing\Domain;

use App\Exceptions\BusinessException;
use App\Testing\CreatesApplicationTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class DomainTestCase extends BaseTestCase
{
    use CreatesApplicationTrait;
    use DatabaseTransactions {
        beginDatabaseTransaction as baseBeginDatabaseTransaction;
    }

    public array $connectionsToTransact = [
        'main',
        'history',
    ];

    public int $userId;

    private static array $pdos = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (isset($this->userId)) {
            setUser($this->userId);
        }
    }

    public function beginDatabaseTransaction(): void
    {
        $connectionNames = [...$this->connectionsToTransact, 'telescope'];

        foreach ($connectionNames as $name) {
            $connection = DB::connection($name);

            if (isset(static::$pdos[$name])) {
                $connection->setPdo(static::$pdos[$name]);
            } else {
                static::$pdos[$name] = $connection->getPdo();
            }
        }

        $this->baseBeginDatabaseTransaction();
    }

    /**
     * @return array<string, mixed> values compared before and after the callback
     */
    abstract protected function getValues(): array;

    /**
     * $error = null: callback must succeed, its exception is rethrown
     * $error = <Entity>Exception::<ERROR>: callback must throw this error, its changes are rolled back
     *
     * @param array<string, array{mixed, mixed}> $expected [key => [before, after]], unlisted values must stay the same
     */
    protected function assertChanges(callable $callback, array $expected = [], ?string $error = null): void
    {
        $before = $this->getValues();
        $exception = $this->process($callback);

        if ($exception && ($error === null || !$exception instanceof BusinessException)) {
            throw $exception;
        }

        $this->assertSame($error, $exception?->error, 'Error');

        $after = $this->getValues();
        $changes = [];

        foreach ($after + $before as $key => $value) {
            $valueBefore = $before[$key] ?? null;
            $valueAfter = $after[$key] ?? null;

            if ($valueBefore !== $valueAfter) {
                $changes[$key] = [$valueBefore, $valueAfter];
            }
        }

        $expectedChanges = $this->formatChanges($expected);
        $actualChanges = $this->formatChanges($changes);
        $mismatches = [];

        foreach ($expectedChanges + $actualChanges as $key => $change) {
            $expectedChange = $expectedChanges[$key] ?? 'not changed';
            $actualChange = $actualChanges[$key] ?? 'not changed';

            if ($expectedChange !== $actualChange) {
                $mismatches[] = "$key: expected $expectedChange, actual $actualChange";
            }
        }

        $this->assertEmpty($mismatches, implode("\n", $mismatches));
    }

    /**
     * Rolls back changes on exception, as in a failed request or command
     */
    private function process(callable $callback): ?\Throwable
    {
        foreach ($this->connectionsToTransact as $name) {
            DB::connection($name)->beginTransaction();
        }

        try {
            $callback();
        } catch (\Throwable $e) {
            foreach ($this->connectionsToTransact as $name) {
                DB::connection($name)->rollBack();
            }

            return $e;
        }

        foreach ($this->connectionsToTransact as $name) {
            DB::connection($name)->commit();
        }

        return null;
    }

    private function formatChanges(array $changes): array
    {
        $formattedChanges = [];
        ksort($changes);

        foreach ($changes as $key => [$before, $after]) {
            $formattedChanges[$key] = "{$this->formatValue($before)} -> {$this->formatValue($after)}";
        }

        return $formattedChanges;
    }

    private function formatValue(mixed $value): string
    {
        return match (true) {
            is_int($value), is_float($value) => (string)num_round($value),
            $value instanceof \UnitEnum => $value->name,
            default => var_export($value, true),
        };
    }
}
