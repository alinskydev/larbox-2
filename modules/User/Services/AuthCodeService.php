<?php

namespace Modules\User\Services;

use App\Base\ModelService;
use Modules\User\Exceptions\AuthCodeException;
use Modules\User\Models\AuthCode;
use Modules\User\Models\User;

/**
 * @property AuthCode $model
 */
class AuthCodeService extends ModelService
{
    public function create(string $username): void
    {
        if (!User::query()->where('username', $username)->exists()) return;

        $authCode = $this->model::query()
            ->where('username', $username)
            ->where('created_at', '>', now()->subMinutes(AuthCode::TTL_MINUTES)->format('Y-m-d H:i:s'))
            ->first();

        if ($authCode) {
            $nextSendAt = date('H:i:s', strtotime("$authCode->created_at +" . AuthCode::TTL_MINUTES . ' minutes'));

            throw new AuthCodeException(AuthCodeException::RESEND_NOT_AVAILABLE, [
                'time' => $nextSendAt,
            ]);
        }

        $this->model::query()->where('username', $username)->delete();
        $this->model->safelySave(['username' => $username]);
    }

    public function verifyCode(string $code): bool
    {
        $isCorrect = $this->model->code === $code;

        if (!$isCorrect) {
            $this->model->attempts_left--;
            $this->model->safelySave();
        }

        return $isCorrect;
    }
}
