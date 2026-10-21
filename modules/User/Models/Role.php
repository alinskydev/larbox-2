<?php

namespace Modules\User\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\Models\Company;
use Modules\Company\Observers\Subscription\SubscriptionCheckLimitObserver;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Observers\RoleObserver;
use Modules\User\Resources\RoleResource;

/**
 * @property int $id
 * @property int $creator_id
 * @property ?int $company_id
 * @property string $type
 * @property string $name
 * @property ?string $description
 * @property array $routes
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $creator
 * @property ?Company $company
 *
 * @property class-string<RoleResource> $resourceClass
 */
class Role extends Model
{
    protected $table = 'user_role';

    protected $casts = [
        'routes' => 'array',
    ];

    public string $resourceClass = RoleResource::class;

    public function observerParams(): array
    {
        return [
            'daily_limit' => 100,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            DailyLimitCreateObserver::class,
            SubscriptionCheckLimitObserver::class,
            RoleObserver::class,
        ]);
    }
}
