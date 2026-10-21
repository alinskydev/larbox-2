<?php

namespace Modules\Trade\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\Models\Company;
use Modules\Stock\Models\Batch;
use Modules\Stock\Models\Stock;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\Trade\Observers\PurchaseObserver;
use Modules\Trade\Services\PurchaseService;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property int $creator_id
 * @property int $company_id
 * @property int $stock_id
 * @property ?int $batch_id
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $creator
 * @property Company $company
 * @property Stock $stock
 * @property ?Batch $batch
 * @property Collection<PurchaseProduct> $products
 */
class Purchase extends Model
{
    protected $table = 'trade_purchase';

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

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id')->withTrashed();
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(PurchaseProduct::class, 'purchase_id');
    }

    public function getService(): PurchaseService
    {
        return new PurchaseService($this);
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            DailyLimitCreateObserver::class,
            PurchaseObserver::class,
        ]);
    }
}
