<?php

namespace App\Integration\DTOs;

class IntegrationRequestOptionsDTO
{
    /**
     * @param array{
     *      response_callback: \Closure,
     *      history_data_callback: \Closure,
     * } $additional
     */
    public function __construct(
        public string $method,
        public string $path,
        public bool|\Closure $shouldSaveHistory,
        public array $additional = [],
    ) {}
}
