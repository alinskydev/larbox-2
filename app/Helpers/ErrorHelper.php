<?php

namespace App\Helpers;

use App\Integration\IntegrationException;
use Illuminate\Http\Client\Response;

class ErrorHelper
{
    public static function abortProjectNotWorkingError(string $project, Response $response): void
    {
        $message = $response->json('message') ?? $response->status();
        $message = is_string($message) ? $message : json_encode($message, JSON_UNESCAPED_UNICODE);

        throw new IntegrationException($project, $message);
    }
}
