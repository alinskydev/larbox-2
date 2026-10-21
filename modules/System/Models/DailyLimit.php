<?php

namespace Modules\System\Models;

use App\Base\Model;

/**
 * @property int $id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $action_type
 * @property string $action_name
 * @property int $remaining
 */
class DailyLimit extends Model
{
    protected $table = 'system_daily_limit';

    public $timestamps = false;
}
