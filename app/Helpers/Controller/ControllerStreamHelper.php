<?php

namespace App\Helpers\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ControllerStreamHelper
{
    public static function download(
        string $name,
        ?string $path = null,
        ?string $content = null,
        bool $shouldBeDeleted = false,
    ): StreamedResponse {
        if ($content === null && $path === null) {
            throw new \InvalidArgumentException('Either $path or $content must be passed');
        }

        return response()->streamDownload(
            callback: function () use ($content, $path, $shouldBeDeleted) {
                $path === null ? print($content) : readfile($path);

                if ($shouldBeDeleted && $path !== null) {
                    unlink($path);
                }
            },
            name: $name,
        );
    }
}
