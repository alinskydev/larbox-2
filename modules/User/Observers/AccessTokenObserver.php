<?php

namespace Modules\User\Observers;

use Modules\User\Models\AccessToken;

class AccessTokenObserver
{
    public function creating(AccessToken $model): void
    {
        $model->ip ??= request()?->ip();
    }

    public function updating(AccessToken $model)
    {
        $isEqualLastUsedAt = $model->last_used_at->format('Y-m-d') === $model->getOriginal('last_used_at')?->format('Y-m-d');

        if ($model->isDirty('last_used_at') && $isEqualLastUsedAt) return false;

        $model->expires_at = date('Y-m-d H:i:s', strtotime('+3 days'));
        $model->ip = request()?->ip();
    }

    // public function created(AccessToken $model): void
    // {
    //     $user = $model->tokenable;
    //     $user->tokens()->orderBy('id', 'DESC')->offset(3)->delete();
    // }
}
