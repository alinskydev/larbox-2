<?php

namespace Http\Common\Record\DTOs;

use App\Base\Model;

class RecordCreateParamsDTO
{
    public function __construct(
        public Model $model,
        public array $nonLocalizedRules = [],
        public array $localizedRules = [],
        public bool $isAccessible = true,
    ) {}
}
