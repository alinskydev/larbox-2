<?php

namespace Modules\Trade\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\Models\Company;
use Modules\Product\Models\Product;
use Modules\Stock\Models\Batch;

/**
 * @property int $id
 * @property int $company_id
 * @property int $sale_id
 * @property int $batch_id
 * @property int $product_id
 * @property float $purchase_price
 * @property float $sale_price
 * @property float $sold_quantity
 * @property float $returned_quantity
 * @property array $product_snapshot
 *
 * @property Company $company
 * @property Sale $sale
 * @property Batch $batch
 * @property Product $product
 */
class SaleProduct extends Model
{
    public $timestamps = false;

    protected $table = 'trade_sale_product';

    protected $casts = [
        'purchase_price' => 'float',
        'sale_price' => 'float',
        'sold_quantity' => 'float',
        'returned_quantity' => 'float',
        'product_snapshot' => 'array',
    ];

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
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
