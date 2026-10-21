<?php

namespace Modules\User\Resources;

use App\Base\Resource;

class ProfileResource extends Resource
{
    public function toArray($request): array
    {
        return array_replace_recursive(parent::toArray($request), [
            'full_name' => "$this->last_name $this->first_name",
        ]);
    }
}
