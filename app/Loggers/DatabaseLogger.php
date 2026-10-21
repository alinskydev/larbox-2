<?php

namespace App\Loggers;

use Monolog\Logger;

class DatabaseLogger
{
    public function __invoke(array $config): Logger
    {
        return new Logger('database', [
            new DatabaseLoggerHandler(),
        ]);
    }
}
