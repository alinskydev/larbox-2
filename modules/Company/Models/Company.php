<?php

namespace Modules\Company\Models;

use App\Base\Model;
use App\Base\Model\ModelSoftDeleteTrait;
use App\Casts\LocalStorage\AsImage;
use App\Observers\CreatorIdObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Company\Observers\CompanyObserver;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Enums\UserTypeEnum;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property ?int $parent_id
 * @property int $creator_id
 * @property int $subscription_id
 * @property string $name
 * @property ?string|array $logo
 * @property string $address
 * @property bool $is_excel_import_running
 * @property string $created_at
 * @property string $updated_at
 *
 * @property ?static $parent
 * @property Collection<static> $children
 * @property Credential $credential
 * @property User $creator
 * @property Subscription $subscription
 * @property User $director
 * @property Collection<User> $responsible_users
 */
class Company extends Model
{
    use ModelSoftDeleteTrait;

    protected $table = 'company';

    protected $casts = [
        'logo' => AsImage::class . ':scaleDown_25|scaleDown_100|scaleDown_500',
    ];

    public function observerParams(): array
    {
        return [
            'daily_limit' => 10,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id')->withTrashed();
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->withTrashed();
    }

    public function credential(): HasOne
    {
        return $this->hasOne(Credential::class, 'company_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function director(): HasOne
    {
        return $this->hasOne(User::class, 'company_id')
            ->where((new User())->getTable() . '.type', UserTypeEnum::COMPANY_DIRECTOR->value)
            ->withTrashed();
    }

    public function responsible_users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_company_responsibility_ref',
            'company_id',
            'user_id',
        )->withTrashed();
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            DailyLimitCreateObserver::class,
            CompanyObserver::class,
        ]);
    }
}
