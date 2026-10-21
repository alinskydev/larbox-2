<?php

namespace Modules\User\Models;

use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\User\Observers\AccessTokenObserver;

/**
 * @property int $id
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property string $name
 * @property string $token
 * @property ?string $abilities
 * @property ?Carbon $last_used_at
 * @property ?string $expires_at
 * @property ?string $ip
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $tokenable
 */
class AccessToken extends PersonalAccessToken
{
    protected $table = 'user_access_token';

    protected static function booted(): void
    {
        self::observe([
            AccessTokenObserver::class,
        ]);
    }
}
