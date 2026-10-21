<?php

namespace Modules\Stock\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\Models\Company;
use Modules\Product\Models\Product as ProductProduct;

/**
 * @property int $id
 * @property int $company_id
 * @property int $stock_id
 * @property int $batch_id
 * @property int $product_id
 * @property float $purchase_price
 * @property float $quantity
 *
 * @property Company $company
 * @property Stock $stock
 * @property Batch $batch
 * @property ProductProduct $product
 */
class Product extends Model
{
    public $timestamps = false;

    protected $table = 'stock_product';

    protected $casts = [
        'purchase_price' => 'float',
        'quantity' => 'float',
    ];

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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProduct::class, 'product_id')->withTrashed();
    }

    protected static function booted(): void
    {
        self::observe([
            CompanyIdObserver::class,
        ]);
    }
}
