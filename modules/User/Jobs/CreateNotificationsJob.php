<?php

namespace Modules\User\Jobs;

use App\Base\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\User\Enums\NotificationTypeEnum;
use Modules\User\Helpers\NotificationHelper;

class CreateNotificationsJob extends Job
{
    public function __construct(
        protected array $userIds,
        protected string $type,
        protected string $text,
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
            NotificationHelper::insertMultiple(
                recipientIds: $this->userIds,
                type: NotificationTypeEnum::caseByValue($this->type),
                params: [
                    'text' => $this->text,
                ],
                creatorId: $this->userId,
            );
        } catch (\Throwable $e) {
            $this->release(60 * 10);
            Log::error($e->getMessage(), ['exception' => $e]);
        }
    }
}
