<?php

namespace Modules\Stock\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\Models\Company;

/**
 * @property int $id
 * @property int $company_id
 * @property int $stock_id
 * @property string $created_at
 *
 * @property Company $company
 * @property Stock $stock
 * @property Collection<Product> $products
 */
class Batch extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'stock_batch';

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id')->withTrashed();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'batch_id');
    }

    protected static function booted(): void
    {
        self::observe([
            CompanyIdObserver::class,
        ]);
    }
}
