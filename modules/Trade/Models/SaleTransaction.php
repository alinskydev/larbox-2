<?php

namespace Modules\Trade\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\Models\Company;

/**
 * @property int $id
 * @property int $company_id
 * @property int $sale_id
 * @property string $type
 * @property string $payment_type
 * @property float $amount
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Company $company
 * @property Sale $sale
 */
class SaleTransaction extends Model
{
    protected $table = 'trade_sale_transaction';

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    protected static function booted(): void
    {
        self::observe([
            CompanyIdObserver::class,
        ]);
    }
}
