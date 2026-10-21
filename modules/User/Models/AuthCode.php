<?php

namespace Modules\User\Models;

use App\Base\Model;
use Modules\User\Observers\AuthCodeObserver;
use Modules\User\Services\AuthCodeService;

/**
 * @property string $username
 * @property string $code
 * @property int $attempts_left
 * @property string $created_at
 */
class AuthCode extends Model
{
    public const UPDATED_AT = null;

    public const MAX_ATTEMPTS = 3;
    public const TTL_MINUTES = 10;

    protected $table = 'user_auth_code';
    protected $primaryKey = 'username';
    protected $keyType = 'string';

    public $incrementing = false;

    public function getService(): AuthCodeService
    {
        return new AuthCodeService($this);
    }

    protected static function booted(): void
    {
        self::observe([
            AuthCodeObserver::class,
        ]);
    }
}
