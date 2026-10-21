<?php

namespace Modules\User\Resources;

use App\Base\Resource;

class NotificationResource extends Resource
{
    public function toArray($request): array
    {
        return array_replace_recursive(parent::toArray($request), [
            'text' => __("user_notifications.$this->type.text", $this->params),
        ]);
    }
}
