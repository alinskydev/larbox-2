<?php

namespace Modules\User\Observers;

use Illuminate\Support\Facades\Hash;
use Modules\User\Exceptions\UserException;
use Modules\User\Models\AuthCode;
use Modules\User\Models\User;

class UserObserver
{
    public function saving(User $model): void
    {
        if (!$model->password) {
            $model->password = $model->getOriginal('password');
        }

        if ($model->isDirty('password')) {
            $model->password = Hash::make($model->password);
        }
    }

    public function created(User $model): void
    {
        if (!$model->profile()->exists()) {
            $model->profile()->create();
        }
    }

    public function updated(User $model): void
    {
        if ($model->wasChanged('password')) {
            $model->tokens()->delete();

            if (user()?->id === $model->id) {
                $model->getService()->createNewAccessToken();
            }

            AuthCode::query()->where(['username' => $model->username])->delete();
        }
    }

    public function deleting(User $model): void
    {
        if ($model->getService()->isSuperadmin()) {
            throw new UserException(UserException::UNDELETABLE);
        }

        if ($model->id === user()->id) {
            throw new UserException(UserException::SELF_DELETION);
        }
    }
}
