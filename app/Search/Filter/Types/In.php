<?php

namespace App\Search\Filter\Types;

use App\Search\Filter\SearchFilterType;

class In extends SearchFilterType
{
    public function process(): void
    {
        $this->query->{"{$this->condition}In"}(
            $this->field,
            (array)$this->value,
        );
    }
}
