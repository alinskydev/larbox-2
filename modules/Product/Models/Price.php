<?php

namespace Modules\Product\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\Models\Company;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property float $price
 * @property string $updated_at
 *
 * @property Company $company
 * @property Product $product
 */
class Price extends Model
{
    public const CREATED_AT = null;

    protected $table = 'product_price';

    protected $casts = [
        'price' => 'float',
    ];

    protected array $autoFilters = [
        'current_company' => ['company_id'],
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    protected static function booted(): void
    {
        self::observe([
            CompanyIdObserver::class,
        ]);
    }
}
