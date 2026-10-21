<?php

namespace App\Search\Filter\Types;

use App\Search\Filter\SearchFilterType;

class BetweenNumber extends SearchFilterType
{
    public function process(): void
    {
        $this->value = (array)$this->value;

        if (isset($this->value[0])) {
            $this->query->{$this->condition}(
                $this->field,
                '>=',
                (float)$this->value[0],
            );
        }

        if (isset($this->value[1])) {
            $this->query->{$this->condition}(
                $this->field,
                '<=',
                (float)$this->value[1],
            );
        }
    }
}
