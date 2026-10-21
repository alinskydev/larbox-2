<?php

namespace Http\Common\Record\DTOs;

use App\Base\EloquentBuilder;
use Kalnoy\Nestedset\QueryBuilder;

class RecordIndexParamsDTO
{
    public function __construct(
        public EloquentBuilder|QueryBuilder $query,
        public string $field,
        public array $additionalFields = [],
        public bool $isAccessible = true,
    ) {}
}
