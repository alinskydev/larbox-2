<?php

namespace App\Observers;

use App\Base\Model;

class CreatorIdObserver
{
    public function creating(Model $model): void
    {
        $model->creator_id ??= user()?->id;
    }
}
