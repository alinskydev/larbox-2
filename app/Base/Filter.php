<?php

namespace App\Base;

use Illuminate\Database\Eloquent\Builder;

abstract class Filter
{
    /**
     * @param EloquentBuilder $query
     */
    abstract public function process(Builder $query): Builder;
}
