<?php

namespace App\Base\Model;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

/**
 * @mixin Model
 */
trait ModelRelationsTrait
{
    public array $filledRelations = [
        'oneToOne' => [],
        'oneToMany' => [],
        'manyToMany' => [],
    ];

    private static array $reversedRelationNames = [];

    public function fillRelations(
        array $oneToOne = [],
        array $oneToMany = [],
        array $manyToMany = [],
    ) {
        $this->filledRelations = [
            'oneToOne' => array_replace($this->filledRelations['oneToOne'], $oneToOne),
            'oneToMany' => array_replace($this->filledRelations['oneToMany'], $oneToMany),
            'manyToMany' => array_replace($this->filledRelations['manyToMany'], $manyToMany),
        ];
    }

    protected static function bootModelRelationsTrait()
    {
        static::created(function ($model) {
            static::saveRelations($model);
        });

        static::updated(function ($model) {
            static::saveRelations($model);
        });
    }

    private static function saveRelations(Model $model): void
    {
        foreach ($model->filledRelations as $type => $relations) {
            foreach ($relations as $name => $value) {
                match ($type) {
                    'oneToOne' => static::saveOneToOneRelation($model, $name, $value),
                    'oneToMany' => static::saveOneToManyRelation($model, $name, $value),
                    'manyToMany' => static::saveManyToManyRelation($model, $name, $value),
                };
            }
        }
    }

    private static function saveOneToOneRelation(Model $model, string $name, array $attributes)
    {
        [$fields, $nested] = static::extractNestedRelations($attributes);

        $relatedModel = $model->$name()->firstOrNew();
        $relatedModel->fill($fields);

        if ($nested) {
            $relatedModel->fillRelations(...$nested);
        }

        $relatedModel->safelySave();

        unset($model->$name);
    }

    private static function saveOneToManyRelation(Model $model, string $name, array $groups)
    {
        /** @var Model */
        $relatedModel = $model->$name()->getModel();
        $reversedRelationName = static::getReversedRelationName($model, $relatedModel);

        $relationsForDelete = array_filter(Arr::pluck($groups, 'id'));

        /** @var Collection */
        $relations = $model->$name()->get()->keyBy('id');
        $relations->except($relationsForDelete)->each->delete();

        foreach (array_values($groups) as $key => $group) {
            [$fields, $nested] = static::extractNestedRelations($group);

            $fields['id'] ??= null;
            $fields['sort_index'] = $key;

            $relation = $relations->get($fields['id']) ?? new $relatedModel();
            $relation->fill($fields);

            if ($reversedRelationName) {
                $relation->setRelation($reversedRelationName, $model);
            }

            if ($nested) {
                $relation->fillRelations(...$nested);
            }

            if (!$relation->exists) {
                $model->$name()->save($relation);
            } elseif ($nested || $relation->isDirty()) {
                $relation->safelySave();
            }
        }

        unset($model->$name);
    }

    private static function saveManyToManyRelation(Model $model, string $name, array $ids)
    {
        $relation = $model->$name();
        $relatedKey = $relation->getRelatedPivotKeyName();

        $newIds = collect($ids)->keyBy(fn($value) => $value[$relatedKey] ?? $value)->all();
        $oldIds = $relation->allRelatedIds()->all();

        if ($detachIds = array_diff($oldIds, array_keys($newIds))) {
            $relation->detach($detachIds);
        }

        if ($attachIds = Arr::except($newIds, $oldIds)) {
            $relation->attach($attachIds);
        }

        unset($model->$name);
    }

    private static function extractNestedRelations(array $attributes): array
    {
        $fields = Arr::except($attributes, ['oneToOne', 'oneToMany', 'manyToMany']);
        $nested = Arr::only($attributes, ['oneToOne', 'oneToMany', 'manyToMany']);

        return [$fields, $nested];
    }

    private static function getReversedRelationName(Model $model, Model $relatedModel): ?string
    {
        $cacheKey = $relatedModel::class . '->' . $model::class;

        if (array_key_exists($cacheKey, static::$reversedRelationNames)) {
            return static::$reversedRelationNames[$cacheKey];
        }

        $name = null;

        foreach ((new \ReflectionClass($relatedModel))->getMethods() as $method) {
            if ($method->class === $relatedModel::class && (string)$method->getReturnType() === BelongsTo::class) {
                if ($method->invoke($relatedModel)->getModel()::class === $model::class) {
                    $name = $method->getName();
                }
            }
        }

        return static::$reversedRelationNames[$cacheKey] = $name;
    }
}
