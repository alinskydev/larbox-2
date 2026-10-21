<?php

namespace Modules\Company\Observers\Subscription;

use App\Base\Model;
use App\Helpers\Database\DatabaseTransactionHelper;
use Modules\Company\Enums\SubscriptionRecordLimitEnum;
use Modules\Company\Exceptions\SubscriptionException;

class SubscriptionCheckLimitObserver
{
    public function creating(Model $model, int $additionalCount = 0): void
    {
        $this->ensureLimit($model, $additionalCount);
    }

    public function restoring(Model $model, int $additionalCount = 0): void
    {
        $this->ensureLimit($model, $additionalCount);
    }

    private function ensureLimit(Model $model, int $additionalCount = 0): void
    {
        $company = user()?->company_id === $model->company_id ? user()->company : $model->company;

        if (!$company) return;

        $limitKey = SubscriptionRecordLimitEnum::valueByClassName($model::class);

        DatabaseTransactionHelper::lock("company_subscription_limit:$limitKey:$company->id");

        $limit = $company->subscription->record_limits[$limitKey];
        $count = $model::query()->where('company_id', $company->id)->count() + $additionalCount + 1;

        if ($count > $limit) {
            $error = $additionalCount
                ? SubscriptionException::RECORD_LIMIT_WILL_BE_EXCEEDED
                : SubscriptionException::RECORD_LIMIT_EXCEEDED;

            throw new SubscriptionException($error, [
                'limit' => $limit,
                'entity' => SubscriptionRecordLimitEnum::labelByValue($limitKey),
            ]);
        }
    }
}
