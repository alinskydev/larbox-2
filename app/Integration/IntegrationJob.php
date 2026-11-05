<?php

namespace App\Integration;

use App\Base\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class IntegrationJob extends Job
{
    /**
     * @param class-string<IntegrationService> $service
     * @param array<string, mixed> $params Named arguments of the service method
     */
    public function __construct(
        private string $service,
        private string $method,
        private array $params = [],
        private array $additionalData = [],
        private int $releaseDelay = 60 * 10,
        string $queue = 'integration',
    ) {
        parent::__construct();
        $this->queue = $queue;
    }

    public function retryUntil(): Carbon
    {
        return now()->addWeek();
    }

    public function handle(): void
    {
        try {
            (new $this->service())
                ->setAdditionalData($this->additionalData)
                ->{$this->method}(...$this->params);
        } catch (\Throwable $e) {
            $this->release($this->releaseDelay);
            Log::error($e->getMessage(), ['exception' => $e]);
        }
    }
}
