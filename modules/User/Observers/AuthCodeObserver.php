<?php

namespace Modules\User\Observers;

use App\Mail\MailHelper;
use Illuminate\Support\Str;
use Modules\User\Mail\AuthCodeMail;
use Modules\User\Models\AuthCode;

class AuthCodeObserver
{
    public function creating(AuthCode $model): void
    {
        $model->code = app()->environment(['local', 'testing']) ? '123456' : Str::random(32);
        $model->attempts_left = AuthCode::MAX_ATTEMPTS;
    }

    public function created(AuthCode $model): void
    {
        MailHelper::send(
            mail: new AuthCodeMail($model->code),
            to: $model->username,
        );
    }

    public function saved(AuthCode $model): void
    {
        if ($model->attempts_left <= 0) $model->delete();
    }
}
