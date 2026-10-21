<?php

namespace Modules\Company\Observers;

use App\Helpers\ArrayHelper;
use Modules\Company\Enums\SubscriptionRecordLimitEnum;
use Modules\Company\Exceptions\SubscriptionException;
use Modules\Company\Models\Company;
use Modules\Company\Models\Subscription;

class SubscriptionObserver
{
    public function saving(Subscription $model): void
    {
        $values = ArrayHelper::fillByTemplate(
            data: $model->record_limits ?? [],
            template: SubscriptionRecordLimitEnum::defaults(),
        );

        $model->record_limits = array_map(fn($value) => (int)$value, $values);
    }

    public function deleting(Subscription $model): void
    {
        $companyNames = Company::query()
            ->where('subscription_id', $model->id)
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get()
            ->pluck('name');

        if (!$companyNames->isEmpty()) {
            throw new SubscriptionException(SubscriptionException::ATTACHED_TO_COMPANIES, [
                'subscription' => $model->name,
                'companies' => $companyNames->join(', '),
            ]);
        }
    }
}
