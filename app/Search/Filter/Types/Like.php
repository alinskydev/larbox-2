<?php

namespace App\Search\Filter\Types;

use App\Search\Filter\SearchFilterType;

class Like extends SearchFilterType
{
    public function process(): void
    {
        $this->query->{"{$this->condition}Ilike"}(
            $this->field,
            $this->value,
        );
    }
}
