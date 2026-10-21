<?php

namespace Modules\User\Resources;

use App\Base\Resource;
use Modules\User\Helpers\RoleHelper;

class RoleResource extends Resource
{
    public function toArray($request): array
    {
        return array_replace_recursive(parent::toArray($request), [
            'pivot' => $this->when(false, null),
            'routes' => RoleHelper::routeList($this->resource),
        ]);
    }
}
