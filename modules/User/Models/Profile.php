<?php

namespace Modules\User\Models;

use App\Base\Model;
use Modules\User\Resources\ProfileResource;

/**
 * @property int $user_id
 * @property ?string $first_name
 * @property ?string $last_name
 * @property ?string $position
 *
 * @property class-string<ProfileResource> $resourceClass
 */
class Profile extends Model
{
    protected $table = 'user_profile';
    protected $primaryKey = 'user_id';

    public $incrementing = false;
    public $timestamps = false;

    protected $hidden = [
        'user_id',
    ];

    public string $resourceClass = ProfileResource::class;
}
