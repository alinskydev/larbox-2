<?php

namespace Modules\User\Helpers;

use Modules\User\Enums\NotificationTypeEnum;
use Modules\User\Models\Notification;

class NotificationHelper
{
    public static function insertMultiple(
        array $recipientIds,
        NotificationTypeEnum $type,
        array $params = [],
        ?int $creatorId = null,
    ): void {
        $createdAt = date('Y-m-d H:i:s');
        $params = json_encode($params);

        $notifications = array_map(
            fn($id) => [
                'creator_id' => $creatorId,
                'recipient_id' => $id,
                'type' => $type->value,
                'params' => $params,
                'created_at' => $createdAt,
            ],
            $recipientIds,
        );

        if ($notifications) {
            Notification::query()->getQuery()->insert($notifications);
        }
    }
}
