<?php

namespace App\Observers;

use App\Base\Model;

class IPObserver
{
    public function creating(Model $model): void
    {
        $model->ip ??= request()?->ip();
    }
}
