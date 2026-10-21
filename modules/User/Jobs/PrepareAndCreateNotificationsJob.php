<?php

namespace Modules\User\Jobs;

use App\Base\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\User\Models\User;
use Modules\User\Search\UserSearch;

class PrepareAndCreateNotificationsJob extends Job
{
    public function __construct(
        protected array $payload,
    ) {
        parent::__construct();
        $this->queue = 'notification';
    }

    public function retryUntil(): Carbon
    {
        return now()->addMonth();
    }

    public function handle(): void
    {
        try {
            (new UserSearch())
                ->setQuery(User::query())
                ->filter($this->payload['filter'] ?? [])
                ->show($this->payload['show'] ?? [])
                ->query
                ->select('id')
                ->chunkById(1000, function ($users) {
                    dispatch(new CreateNotificationsJob(
                        userIds: $users->pluck('id')->toArray(),
                        type: $this->payload['type'],
                        text: $this->payload['text'],
                    ));
                });
        } catch (\Throwable $e) {
            $this->release(60 * 10);
            Log::error($e->getMessage(), ['exception' => $e]);
        }
    }
}
