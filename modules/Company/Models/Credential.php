<?php

namespace Modules\Company\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\Observers\CredentialObserver;
use Modules\Company\Resources\CredentialResource;

/**
 * @property int $company_id
 * @property array $telegram_chats
 * @property array $store
 *
 * @property Company $company
 * @property class-string<CredentialResource> $resourceClass
 */
class Credential extends Model
{
    protected $table = 'company_credential';

    protected $primaryKey = 'company_id';
    public $incrementing = false;

    public $timestamps = false;

    protected $hidden = [
        'company_id',
    ];

    protected $casts = [
        'telegram_chats' => 'array',
        'store' => 'encrypted:array',
    ];

    public string $resourceClass = CredentialResource::class;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    protected static function booted(): void
    {
        self::observe([
            CredentialObserver::class,
        ]);
    }
}
