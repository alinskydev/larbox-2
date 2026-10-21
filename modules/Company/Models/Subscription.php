<?php

namespace Modules\Company\Models;

use App\Base\Model;
use App\Observers\CreatorIdObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\Observers\SubscriptionObserver;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property int $creator_id
 * @property string $name
 * @property ?string $description
 * @property array $record_limits
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $creator
 */
class Subscription extends Model
{
    protected $table = 'company_subscription';

    protected $casts = [
        'record_limits' => 'array',
    ];

    public function observerParams(): array
    {
        return [
            'daily_limit' => 10,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            DailyLimitCreateObserver::class,
            SubscriptionObserver::class,
        ]);
    }
}
