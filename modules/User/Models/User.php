<?php

namespace Modules\User\Models;

use App\Base\Model;
use App\Base\Model\ModelSoftDeleteTrait;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Company\Models\Company;
use Modules\Company\Observers\Subscription\SubscriptionCheckLimitObserver;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Builders\UserBuilder;
use Modules\User\Observers\UserObserver;
use Modules\User\Services\UserService;

/**
 * @property int $id
 * @property int $creator_id
 * @property ?int $company_id
 * @property string $type
 * @property string $username
 * @property string $password
 * @property ?string $phone
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Profile $profile
 * @property User $creator
 * @property ?Company $company
 * @property Collection<Role> $roles
 * @property Collection<Company> $responsible_companies
 *
 * @method static UserBuilder query()
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable;
    use Authorizable;
    use HasApiTokens;

    use ModelSoftDeleteTrait;

    protected $table = 'user';

    protected $hidden = [
        'password',
    ];

    public array $nestedCompanyIds;
    public array $holdingCompanyIds;
    public array $administeredCompanyIds;

    public array $allRoleRoutes;

    public string $newAccessToken;

    public function observerParams(): array
    {
        return [
            'daily_limit' => 100,
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(static::class, 'creator_id')->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role_ref', 'user_id', 'role_id');
    }

    public function responsible_companies(): BelongsToMany
    {
        return $this->belongsToMany(
            Company::class,
            'user_company_responsibility_ref',
            'user_id',
            'company_id',
        )->withTrashed();
    }

    public function getService(): UserService
    {
        return new UserService($this);
    }

    public function newEloquentBuilder($query, $builderClass = null): UserBuilder
    {
        return parent::newEloquentBuilder($query, UserBuilder::class);
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            DailyLimitCreateObserver::class,
            SubscriptionCheckLimitObserver::class,
            UserObserver::class,
        ]);
    }
}
