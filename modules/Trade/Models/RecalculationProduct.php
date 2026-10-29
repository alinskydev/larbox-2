<?php

namespace Modules\Trade\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\Models\Company;
use Modules\Product\Models\Product;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\Trade\Observers\RecalculationProductObserver;
use Modules\Trade\Services\RecalculationProductService;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property int $creator_id
 * @property int $company_id
 * @property int $product_id
 * @property float $price
 * @property string $created_at
 *
 * @property User $creator
 * @property Company $company
 * @property Product $product
 */
class RecalculationProduct extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'trade_recalculation_product';

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
        ];
    }

    public function observerParams(): array
    {
        return [
            'daily_limit' => 2000,
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    public function getService(): RecalculationProductService
    {
        return new RecalculationProductService($this);
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            DailyLimitCreateObserver::class,
            RecalculationProductObserver::class,
        ]);
    }
}
