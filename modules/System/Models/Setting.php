<?php

namespace Modules\System\Models;

use App\Base\Model;
use Modules\System\Resources\SettingResource;

/**
 * @property string $name
 * @property string $value
 *
 * @property class-string<SettingResource> $resourceClass
 */
class Setting extends Model
{
    protected $table = 'system_setting';

    protected $primaryKey = 'name';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    public string $resourceClass = SettingResource::class;
}
