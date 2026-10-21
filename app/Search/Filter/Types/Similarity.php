<?php

namespace App\Search\Filter\Types;

use App\Search\Filter\SearchFilterType;

class Similarity extends SearchFilterType
{
    public function process(): void
    {
        $this->query->{"{$this->condition}Raw"}(
            // "similarity($this->field, ?) > 0.3",
            "$this->field % ?",
            [$this->value],
        );
    }
}
