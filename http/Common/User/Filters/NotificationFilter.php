<?php

namespace Http\Common\User\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;

class NotificationFilter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query->filterByUser('recipient_id');
    }
}
