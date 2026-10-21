<?php

namespace App\Integration\DTOs;

use App\Base\Model;

class IntegrationServiceConfigDTO
{
    public function __construct(
        public string $name,
        public string $url,
        public string $username,
        public string $password,
        public bool $hasOwnAccount,
        public Model $historyModel,
        public array $additional = [],
    ) {}
}
