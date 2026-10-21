<?php

namespace App\NestedSet;

use App\Base\EloquentBuilder;
use App\Base\EloquentBuilder\EloquentBuilderFilterByPermissionsTrait;
use App\Base\EloquentBuilder\EloquentBuilderFilterByUserTrait;
use App\Base\Model;
use App\Base\QueryBuilder;
use Illuminate\Support\Arr;
use Kalnoy\Nestedset\AncestorsRelation;
use Kalnoy\Nestedset\QueryBuilder as KalnoyEloquentBuilder;

class NestedSetEloquentBuilder extends KalnoyEloquentBuilder
{
    use EloquentBuilderFilterByUserTrait;
    use EloquentBuilderFilterByPermissionsTrait;

    public function whereLower(string $field, string $value): static
    {
        $field = str_replace(["'", '->>'], ['', '->'], $field);
        $field = $this->getQuery()->getGrammar()->wrap($field);

        return $this->whereRaw("LOWER({$field}) = LOWER(?)", [$value]);
    }

    public function getQueryWithRelations(): QueryBuilder
    {
        return $this->query->setParams(
            model: $this->model,
            relations: $this->eagerLoad,
            additionalRelations: [
                AncestorsRelation::class => function ($records, $relation, $name, $model, $callback) {
                    return $this->processRelationAncestors($records, $relation, $name, $model, $callback);
                },
            ],
        );
    }

    private function processRelationAncestors(
        array &$records,
        AncestorsRelation $relation,
        string $name,
        Model $model,
        array|callable $callback,
    ): void {
        $pk = 'id';

        $ids = Arr::pluck($records, $pk);
        $ids = array_unique($ids);

        $table = $model->getTable();
        $parentTable = "{$table}_parent";

        /** @var EloquentBuilder<Model>|NestedSetEloquentBuilder<Model> */
        $relationQuery = $model->query();
        $relatedRecords = $relationQuery
            ->join("$table as $parentTable", function ($join) use ($table, $parentTable) {
                $join
                    ->whereRaw("{$table}.lft > {$parentTable}.lft")
                    ->whereRaw("{$table}.rgt < {$parentTable}.rgt");
            })
            ->when(
                is_array($callback),
                function ($query) use ($callback, $table, $parentTable) {
                    $query->with($callback);
                    $query->select(["$parentTable.*", "$table.id as child_id"]);
                },
                function ($query) use ($callback, $table, $parentTable) {
                    $callback($query);

                    $columns = $query->getQuery()->columns ?? ["$parentTable.*"];
                    $columns[] = "$table.id as child_id";
                    $query->select($columns);
                },
            )
            ->whereNotNull("$parentTable.parent_id")
            ->filterByHoldingCompanies("$parentTable.company_id")
            ->orderBy("$parentTable.lft")
            ->getQueryWithRelations()
            ->whereInThroughAny("$table.$pk", $ids)
            ->get()
            ->groupBy('child_id');

        foreach ($records as $record) {
            $record->$name = $relatedRecords->get($record->$pk)?->toArray() ?? [];
        }
    }
}
