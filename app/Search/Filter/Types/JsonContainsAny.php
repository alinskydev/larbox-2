<?php

namespace App\Search\Filter\Types;

use App\Search\Filter\SearchFilterType;

class JsonContainsAny extends SearchFilterType
{
    public function process(): void
    {
        $this->query->{$this->condition}(function ($query) {
            foreach ((array)$this->value as $v) {
                $query->orWhereJsonContains($this->field, $v);
            }
        });
    }
}
