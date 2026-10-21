<?php

namespace App\Base\QueryBuilder;

use App\Base\EloquentBuilder;
use App\Base\Model;
use App\Base\QueryBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;

/**
 * @mixin QueryBuilder
 */
trait QueryBuilderRelationsTrait
{
    private function processRelationBelongsTo(
        array &$records,
        BelongsTo $relation,
        string $name,
        Model $model,
        array|callable $callback,
    ): void {
        $this->clearRelatedWheres(
            relation: $relation,
            column: $relation->getQualifiedOwnerKeyName(),
        );

        $pk = $relation->getOwnerKeyName();
        $fk = $relation->getForeignKeyName();

        $ids = Arr::pluck($records, $fk);
        $ids = array_unique($ids);

        /** @var EloquentBuilder<Model> */
        $relationQuery = $relation->getQuery();
        $relatedRecords = $relationQuery
            ->when(
                is_array($callback),
                function ($query) use ($callback) {
                    $query->with($callback);
                },
                function ($query) use ($callback) {
                    $callback($query);
                },
            )
            ->getQueryWithRelations()
            ->whereInThroughAny($model->getTable() . ".$pk", $ids)
            ->get()
            ->keyBy($pk);

        foreach ($records as $record) {
            $record->$name = $relatedRecords->get($record->$fk);
        }
    }

    private function processRelationHasOne(
        array &$records,
        HasOne $relation,
        string $name,
        Model $model,
        array|callable $callback,
    ): void {
        $this->clearRelatedWheres(
            relation: $relation,
            column: $relation->getQualifiedForeignKeyName(),
        );

        $fk = $relation->getForeignKeyName();
        $lk = $relation->getLocalKeyName();

        $ids = Arr::pluck($records, $lk);
        $ids = array_unique($ids);

        /** @var EloquentBuilder<Model> */
        $relationQuery = $relation->getQuery();
        $relatedRecords = $relationQuery
            ->when(
                is_array($callback),
                function ($query) use ($callback) {
                    $query->with($callback);
                },
                function ($query) use ($callback) {
                    $callback($query);
                },
            )
            ->getQueryWithRelations()
            ->whereInThroughAny($model->getTable() . ".$fk", $ids)
            ->get()
            ->keyBy($fk);

        foreach ($records as $record) {
            $record->$name = $relatedRecords->get($record->$lk);
        }
    }

    private function processRelationHasMany(
        array &$records,
        HasMany $relation,
        string $name,
        Model $model,
        array|callable $callback,
    ): void {
        $this->clearRelatedWheres(
            relation: $relation,
            column: $relation->getQualifiedForeignKeyName(),
        );

        $fk = $relation->getForeignKeyName();
        $lk = $relation->getLocalKeyName();

        $ids = Arr::pluck($records, $lk);
        $ids = array_unique($ids);

        /** @var EloquentBuilder<Model> */
        $relationQuery = $relation->getQuery();
        $relatedRecords = $relationQuery
            ->when(
                is_array($callback),
                function ($query) use ($callback) {
                    $query->with($callback);
                },
                function ($query) use ($callback) {
                    $callback($query);
                },
            )
            ->getQueryWithRelations()
            ->whereInThroughAny($model->getTable() . ".$fk", $ids)
            ->get()
            ->groupBy($fk);

        foreach ($records as $record) {
            $record->$name = $relatedRecords->get($record->$lk)?->toArray() ?? [];
        }
    }

    private function processRelationBelongsToMany(
        array &$records,
        BelongsToMany $relation,
        string $name,
        Model $model,
        array|callable $callback,
    ): void {
        $this->clearRelatedWheres(
            relation: $relation,
            column: $relation->getQualifiedForeignPivotKeyName(),
        );

        $parentKey = $relation->getParentKeyName();
        $foreignPivotKey = $relation->getForeignPivotKeyName();

        $pivotTable = $relation->getTable();
        $relatedTable = $model->getTable();

        $ids = Arr::pluck($records, $parentKey);
        $ids = array_unique($ids);

        /** @var EloquentBuilder<Model> */
        $relationQuery = $relation->getQuery();
        $relatedRecords = $relationQuery
            ->when(
                is_array($callback),
                function ($query) use ($callback, $relatedTable, $pivotTable, $foreignPivotKey) {
                    $query->with($callback);
                    $query->select(["$relatedTable.*", "$pivotTable.$foreignPivotKey as pivot_$foreignPivotKey"]);
                },
                function ($query) use ($callback, $relatedTable, $pivotTable, $foreignPivotKey) {
                    $callback($query);

                    $columns = $query->getQuery()->columns ?? ["$relatedTable.*"];
                    $columns[] = "$pivotTable.$foreignPivotKey as pivot_$foreignPivotKey";
                    $query->select($columns);
                },
            )
            ->getQueryWithRelations()
            ->whereInThroughAny("$pivotTable.$foreignPivotKey", $ids)
            ->get()
            ->groupBy("pivot_$foreignPivotKey");

        foreach ($records as $record) {
            $record->$name = $relatedRecords->get($record->$parentKey)?->toArray() ?? [];
        }
    }

    private function clearRelatedWheres(Relation $relation, string $column): void
    {
        $query = $relation->getBaseQuery();
        $query->wheres = array_filter(
            $query->wheres,
            fn($value) => !isset($value['column']) || $value['column'] !== $column,
        );
    }
}
