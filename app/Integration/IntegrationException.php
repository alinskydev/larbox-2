<?php

namespace App\Integration;

class IntegrationException extends \Exception
{
    public function __construct(public string $service, string $message)
    {
        parent::__construct(__('Произошла ошибка в сервисе ":service" (:message)', [
            'service' => $service,
            'message' => $message,
        ]));
    }
}
