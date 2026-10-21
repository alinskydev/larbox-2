<?php

namespace Modules\Audit\Observers\RecordAction;

use App\Base\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Audit\Enums\RecordActionTypeEnum;
use Modules\Audit\Traits\RecordActionModelTrait;

class RecordActionCreateObserver
{
    /**
     * @param Model&RecordActionModelTrait $model
     */
    public function updating(Model $model): void
    {
        $model->recordActionOldData['attributes'] = $model->getOriginal();

        $params = $model->getService()->checkAndGetObserverParams('audit_record_action');
        $params['excluded'] ??= [];
        $params['relations'] ??= [];

        $relationTypes = [
            'belongsTo' => function ($relation, $path) use ($model) {
                $oldValue = $model->getOriginal($relation);
                $newValue = $model->$relation;

                if ((string)$oldValue === (string)$newValue) return $newValue;

                $name = Str::before($path, '.');

                $model->unsetRelation($name)->$relation = $oldValue;
                $oldValue = data_get($model, $path);
                $model->unsetRelation($name)->$relation = $newValue;

                return $oldValue;
            },
            'oneToMany' => fn($relation, $fields) => array_map(
                fn($value) => Arr::only($value, $fields),
                array_values(data_get($model, $relation)?->toArray() ?? []),
            ),
            'manyToMany' => fn($relation, $path) => array_values(data_get($model, $path)),
        ];

        foreach ($relationTypes as $type => $process) {
            $relations = Arr::get($params['relations'], $type, []);

            foreach ($relations as $relation => $config) {
                $model->recordActionOldData['relations'][$type][$relation] = $process($relation, $config);
            }
        }
    }

    /**
     * @param Model&RecordActionModelTrait $model
     */
    public function saved(Model $model): void
    {
        $model->getRecordActionCreateService()->process(
            type: $model->wasRecentlyCreated ? RecordActionTypeEnum::CREATE : RecordActionTypeEnum::UPDATE,
            attributeGroups: [
                'old' => $model->recordActionOldData['attributes'],
                'new' => $model->attributesToArray(),
            ],
        );
    }

    /**
     * @param Model&RecordActionModelTrait $model
     */
    public function deleted(Model $model): void
    {
        $model->getRecordActionCreateService()->process(
            type: RecordActionTypeEnum::DELETE,
            attributeGroups: [
                'old' => ['+'],
                'new' => ['-'],
            ],
        );
    }

    /**
     * @param Model&RecordActionModelTrait $model
     */
    public function restoring(Model $model): void
    {
        $model->getRecordActionCreateService()->process(
            type: RecordActionTypeEnum::RESTORE,
            attributeGroups: [
                'old' => ['-'],
                'new' => ['+'],
            ],
        );
    }
}
