<?php

namespace App\Search;

use App\Base\EloquentBuilder;
use App\Helpers\ClassHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class SearchShow
{
    /**
     * @param EloquentBuilder $query
     */
    public function __construct(
        private Builder $query,
        private array $params,
    ) {}

    public function process(): void
    {
        $model = $this->query->getModel();
        $hasSoftDelete = ClassHelper::uses($model, SoftDeletes::class);

        if (!$hasSoftDelete) return;

        if (in_array('with_deleted', $this->params)) {
            $this->query->withTrashed();
        }

        if (in_array('only_deleted', $this->params)) {
            $this->query->onlyTrashed();
        }
    }
}
