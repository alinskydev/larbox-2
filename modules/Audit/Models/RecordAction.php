<?php

namespace Modules\Audit\Models;

use App\Base\Model;
use App\Observers\CreatorIdObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Audit\Resources\RecordActionResource;
use Modules\Company\Models\Company;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property ?int $creator_id
 * @property ?int $company_id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $type
 * @property array $old_values
 * @property array $new_values
 * @property string $created_at
 *
 * @property User $creator
 * @property Company $company
 *
 * @property class-string<RecordActionResource> $resourceClass
 */
class RecordAction extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_record_action';

    protected $hidden = [
        'auditable_type',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public string $resourceClass = RecordActionResource::class;

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
        ]);
    }
}
