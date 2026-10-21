<?php

namespace App\Loggers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class DatabaseLoggerHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            /** @var \Throwable $exception */
            $exception = $record->context['exception'] ?? new \Exception($record->message);
            $trace = Arr::only($exception->getTrace(), range(0, 6));
            $statusCode = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;

            $message = $exception::class . " ({$exception->getMessage()})";
            $trace = json_encode($trace, JSON_UNESCAPED_SLASHES);

            DB::table('audit_error_log')->insert([
                'user_id' => user()?->id,
                'level' => $record->level->name,
                'source' => str_replace(base_path(), '', $exception->getFile()) . ':' . $exception->getLine(),
                'message' => $this->hideSecrets($message),
                'trace' => $this->hideSecrets($trace),
                'request_info' => app()->runningInConsole() ? '[]' : json_encode([
                    'status' => $statusCode,
                    'method' => request()->method(),
                    'path' => request()->path(),
                    // 'body' => $statusCode === 429 ? request()->all() : [],
                    'ip' => request()->ip(),
                ]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Log::channel('daily')->error($e->getMessage(), ['exception' => $e]);
        }
    }

    private function hideSecrets(string $value): string
    {
        $secrets = [
            (string)config('integrations.telegram_bot.url') => '{telegram_bot}',
        ];

        return str_replace(array_keys($secrets), array_values($secrets), $value);
    }
}
