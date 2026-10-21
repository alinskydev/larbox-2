<?php

namespace Modules\System\Enums;

use App\Base\EnumTrait;

enum EventTypeEnum: string
{
    use EnumTrait;

    case DEPLOY = 'deploy';
}
