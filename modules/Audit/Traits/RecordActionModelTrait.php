<?php

namespace Modules\Audit\Traits;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Audit\Models\RecordAction;
use Modules\Audit\Observers\RecordAction\RecordActionCreateObserver;
use Modules\Audit\Services\RecordAction\RecordActionCreateService;

/**
 * @mixin Model
 */
trait RecordActionModelTrait
{
    public array $recordActionOldData = [
        'attributes' => [],
        'relations' => [
            'belongsTo' => [],
            'oneToMany' => [],
            'manyToMany' => [],
        ],
    ];

    public function audit_record_actions(): MorphMany
    {
        return $this->morphMany(RecordAction::class, 'auditable');
    }

    public function getRecordActionCreateService(): RecordActionCreateService
    {
        return new RecordActionCreateService($this);
    }

    protected static function bootRecordActionModelTrait(): void
    {
        $modelClass = static::class;

        app('events')->listen("eloquent.booted: {$modelClass}", function () use ($modelClass) {
            $modelClass::observe(RecordActionCreateObserver::class);
        });
    }
}
