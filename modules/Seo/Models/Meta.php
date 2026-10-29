<?php

namespace Modules\Seo\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Seo\Observers\MetaObserver;

/**
 * @property int $id
 * @property int $company_id
 * @property string $seo_metable_type
 * @property int $seo_metable_id
 * @property array $value
 * @property array $parsed_value
 */
class Meta extends Model
{
    protected $table = 'seo_meta';

    public $timestamps = false;

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'parsed_value' => 'array',
        ];
    }

    public function seo_metable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        self::observe([
            CompanyIdObserver::class,
            MetaObserver::class,
        ]);
    }
}
