<?php

namespace Modules\History\Models;

use App\Base\Model;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use App\Observers\IPObserver;
use Modules\History\Observers\StoreHttpLogObserver;
use Modules\History\Resources\StoreHttpLogResource;

/**
 * @property int $id
 * @property int $creator_id
 * @property int $company_id
 * @property string $request_path
 * @property array $request_body
 * @property array $response_body
 * @property int $response_status
 * @property array $additional
 * @property ?string $ip
 * @property string $created_at
 *
 * @property class-string<StoreHttpLogResource> $resourceClass
 */
class StoreHttpLog extends Model
{
    public const UPDATED_AT = null;

    protected $connection = 'history';
    protected $table = 'history_store';

    public string $resourceClass = StoreHttpLogResource::class;

    protected function casts(): array
    {
        return [
            'request_body' => 'array',
            'response_body' => 'array',
            'additional' => 'array',
        ];
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            IPObserver::class,
            StoreHttpLogObserver::class,
        ]);
    }
}
