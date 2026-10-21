<?php

namespace App\Base;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Support\Carbon;

/**
 * @property DatabaseJob $job
 */
class Job implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 600;

    public bool $failOnTimeout = false;

    protected ?int $userId = null;
    protected ?string $locale = null;

    public function __construct()
    {
        $this->userId = user()?->id;
        $this->locale = app()->getLocale();
    }

    public function retryUntil(): Carbon
    {
        return now()->addYear();
    }

    /**
     * Forbidden: Laravel may call it outside middleware(), without user and locale.
     * Handle failures inside handle() catch block instead.
     */
    final public function failed(\Throwable $e): void {}

    final public function middleware(): array
    {
        return [
            function (self $job, \Closure $next) {
                if ($job->userId !== null) setUser($job->userId, true);
                if ($job->locale !== null) app()->setLocale($job->locale);

                try {
                    return $next($job);
                } finally {
                    setUser(null);
                }
            },
        ];
    }
}
