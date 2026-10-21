<?php

namespace Integrations\Telegram\Jobs;

use App\Base\Job;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\SerializableClosure\SerializableClosure;

class TelegramJob extends Job
{
    public function __construct(
        private SerializableClosure $callback,
    ) {
        parent::__construct();
        $this->queue = 'telegram';
    }

    public function retryUntil(): Carbon
    {
        return now()->addDay();
    }

    public function handle(): void
    {
        try {
            $this->callback->getClosure()();
        } catch (\Throwable $e) {
            $this->isClientError($e) ? $this->fail($e) : $this->release(60 * 10);
            Log::error($e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Chat not found, bot blocked, bad request: retrying won't help.
     */
    private function isClientError(\Throwable $e): bool
    {
        if (!$e instanceof RequestException) {
            return false;
        }

        return $e->response->clientError() && !$e->response->tooManyRequests();
    }
}
