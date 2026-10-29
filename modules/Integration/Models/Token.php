<?php

namespace Modules\Integration\Models;

use App\Base\Model;

/**
 * @property int $id
 * @property string $service_name
 * @property string $username
 * @property string $token
 * @property string $created_at
 */
class Token extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'integration_token';

    protected $hidden = [
        'token',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
        ];
    }
}
