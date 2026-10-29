<?php

namespace Modules\User\Models;

use App\Base\Model;
use App\Observers\CreatorIdObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Resources\NotificationResource;

/**
 * @property int $id
 * @property ?int $creator_id
 * @property int $recipient_id
 * @property string $type
 * @property array $params
 * @property bool $is_seen
 * @property string $created_at
 *
 * @property ?User $creator
 * @property User $recipient
 *
 * @property class-string<NotificationResource> $resourceClass
 */
class Notification extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'user_notification';

    protected $hidden = [
        'creator_id',
        'recipient_id',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public string $resourceClass = NotificationResource::class;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id')->withTrashed();
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
        ]);
    }
}
