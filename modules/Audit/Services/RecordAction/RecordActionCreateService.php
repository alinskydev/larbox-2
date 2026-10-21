<?php

namespace Modules\Audit\Services\RecordAction;

use App\Base\ModelService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Audit\Enums\RecordActionTypeEnum;
use Modules\Audit\Models\RecordAction;

class RecordActionCreateService extends ModelService
{
    /**
     * @var array{
     *      excluded: string[],
     *      relations: array{belongsTo: array, oneToMany: array, manyToMany: array},
     * }
     */
    private array $params;

    private RecordActionTypeEnum $type;

    /**
     * @param array{old: array, new: array} $attributeGroups
     */
    public function process(RecordActionTypeEnum $type, array $attributeGroups): void
    {
        $companyId = $this->model->company_id ?? user()?->company_id;

        if (!$companyId) return;

        $this->type = $type;

        $this->params = $this->model->getService()->checkAndGetObserverParams('audit_record_action');
        $this->params['excluded'] ??= [];
        $this->params['relations'] ??= [];

        if (in_array($this->type, [
            RecordActionTypeEnum::CREATE,
            RecordActionTypeEnum::UPDATE,
        ])) {
            foreach ($attributeGroups as $group => &$attributes) {
                if ($this->model->wasRecentlyCreated && $group === 'old') continue;

                $attributes = Arr::dot($attributes);
                $attributes = $this->exclude($attributes);
                $attributes = $this->appendRelationValues($attributes, $group);
                $attributes = Arr::undot($attributes);
            }

            $attributeGroups = $this->removeSameValues($attributeGroups);
        }

        if ($attributeGroups['new'] !== $attributeGroups['old']) {
            (new RecordAction())->safelySave([
                'company_id' => $companyId,
                'auditable_type' => $this->model::class,
                'auditable_id' => $this->model->id,
                'type' => $this->type->value,
                'old_values' => $attributeGroups['old'],
                'new_values' => $attributeGroups['new'],
            ]);
        }
    }

    private function exclude(array $attributes): array
    {
        $this->params['excluded'] = array_merge(
            $this->params['excluded'],
            ['id', 'company_id', 'creator_id', 'created_at', 'updated_at', 'deleted_at', 'is_deleted'],
        );

        return array_filter($attributes, fn($key) => !Str::is($this->params['excluded'], $key), ARRAY_FILTER_USE_KEY);
    }

    private function appendRelationValues(array $attributes, string $group): array
    {
        $relationTypes = [
            'belongsTo' => function ($relation, $path) use ($group) {
                if ($group === 'old') return $this->model->recordActionOldData['relations']['belongsTo'][$relation] ?? null;

                $oldValue = $this->model->recordActionOldData['attributes'][$relation] ?? null;
                $newValue = $this->model->$relation;

                if ((string)$oldValue === (string)$newValue) return $newValue;

                return data_get($this->model, $path);
            },
            'oneToMany' => function ($relation, $fields) use ($group) {
                if ($group === 'old') return $this->model->recordActionOldData['relations']['oneToMany'][$relation] ?? [];

                return array_map(
                    fn($value) => Arr::only($value, $fields),
                    array_values(data_get($this->model->unsetRelation($relation), $relation)?->toArray() ?? []),
                );
            },
            'manyToMany' => function ($relation, $path) use ($group) {
                if ($group === 'old') return $this->model->recordActionOldData['relations']['manyToMany'][$relation] ?? [];

                return array_values(data_get($this->model->unsetRelation($relation), $path));
            },
        ];

        foreach ($relationTypes as $type => $process) {
            $relations = Arr::get($this->params['relations'], $type, []);

            foreach ($relations as $relation => $config) {
                $attributes[$relation] = $process($relation, $config);
            }
        }

        return $attributes;
    }

    public function removeSameValues(array $attributeGroups): array
    {
        foreach ($attributeGroups['new'] as $field => $newValue) {
            if (!array_key_exists($field, $attributeGroups['old'])) continue;

            if ($attributeGroups['old'][$field] == $newValue) {
                unset($attributeGroups['old'][$field]);
                unset($attributeGroups['new'][$field]);
            }
        }

        return $attributeGroups;
    }
}
