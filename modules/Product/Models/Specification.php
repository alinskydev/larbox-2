<?php

namespace Modules\Product\Models;

use App\Base\Model;
use App\Base\Model\ModelSoftDeleteTrait;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Traits\RecordActionModelTrait;
use Modules\Company\Models\Company;
use Modules\Company\Observers\Subscription\SubscriptionCheckLimitObserver;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property int $creator_id
 * @property int $company_id
 * @property array $name
 * @property array $description
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $creator
 * @property Company $company
 * @property Collection<Option> $options
 */
class Specification extends Model
{
    use ModelSoftDeleteTrait;
    use RecordActionModelTrait;

    protected $table = 'product_specification';

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
    ];

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    public function observerParams(): array
    {
        return [
            'daily_limit' => 500,
            'audit_record_action' => [
                'relations' => [
                    'oneToMany' => [
                        'options' => ['name'],
                    ],
                ],
            ],
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

    public function options(): HasMany
    {
        return $this->hasMany(Option::class, 'specification_id');
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            DailyLimitCreateObserver::class,
            SubscriptionCheckLimitObserver::class,
        ]);
    }
}
