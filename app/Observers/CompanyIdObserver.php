<?php

namespace App\Observers;

use App\Base\Model;

class CompanyIdObserver
{
    public function creating(Model $model): void
    {
        $model->company_id ??= user()?->company_id;
    }
}
