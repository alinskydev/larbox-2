<?php

namespace App\Base;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class Resource extends JsonResource
{
    protected bool $isCollection = false;

    public function toArray($request): mixed
    {
        $relations = Arr::map(
            $this->resource->getRelations(),
            function ($relation, $key) {
                $resource = $relation instanceof Collection ? $relation->first() : $relation;
                $class = $resource->resourceClass ?? static::class;

                return $relation instanceof Collection
                    ? $class::collection($this->whenLoaded($key))
                    : $class::make($this->whenLoaded($key));
            },
        );

        return array_merge($this->resource->attributesToArray(), $relations);
    }

    public static function collection($resource): AnonymousResourceCollection
    {
        $collection = parent::collection($resource);
        $collection->collection?->each(fn($item) => $item->isCollection = true);

        return $collection;
    }
}
