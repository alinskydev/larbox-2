<?php

namespace Modules\Trade\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\Models\Company;
use Modules\Product\Models\Product;

/**
 * @property int $id
 * @property int $company_id
 * @property int $purchase_id
 * @property int $product_id
 * @property float $purchase_price
 * @property float $sale_price
 * @property float $quantity
 *
 * @property Company $company
 * @property Purchase $purchase
 * @property Product $product
 */
class PurchaseProduct extends Model
{
    public $timestamps = false;

    protected $table = 'trade_purchase_product';

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'float',
            'sale_price' => 'float',
            'quantity' => 'float',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
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
