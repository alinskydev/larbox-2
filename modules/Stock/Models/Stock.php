<?php

namespace Modules\Stock\Models;

use App\Base\Model;
use App\Base\Model\ModelSoftDeleteTrait;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\Models\Company;
use Modules\Stock\Services\StockService;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property int $creator_id
 * @property int $company_id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $creator
 * @property Company $company
 * @property Collection<Batch> $batches
 * @property Collection<Product> $products
 */
class Stock extends Model
{
    use ModelSoftDeleteTrait;

    protected $table = 'stock';

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

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

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'stock_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'stock_id');
    }

    public function getService(): StockService
    {
        return new StockService($this);
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            DailyLimitCreateObserver::class,
        ]);
    }
}
