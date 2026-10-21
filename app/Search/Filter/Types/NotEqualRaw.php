<?php

namespace App\Search\Filter\Types;

use App\Search\Filter\SearchFilterType;

class NotEqualRaw extends SearchFilterType
{
    public function process(): void
    {
        $this->query->{$this->condition}(
            $this->field,
            '!=',
            $this->value,
        );
    }
}
