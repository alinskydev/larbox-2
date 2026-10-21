<?php

namespace App\Search\Filter\Types;

use App\Search\Filter\SearchFilterType;
use Illuminate\Support\Facades\DB;

class LocalizedLike extends SearchFilterType
{
    public function process(): void
    {
        $locale = app()->getLocale();

        $this->query->{"{$this->condition}Ilike"}(
            DB::raw("$this->field->>'$locale'"),
            $this->value,
        );
    }
}
