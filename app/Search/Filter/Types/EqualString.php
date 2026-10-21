<?php

namespace App\Search\Filter\Types;

use App\Search\Filter\SearchFilterType;

class EqualString extends SearchFilterType
{
    public function process(): void
    {
        $this->query->{"{$this->condition}Raw"}(
            "lower($this->field) = lower(?)",
            [$this->value],
        );
    }
}
