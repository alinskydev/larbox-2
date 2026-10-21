<?php

namespace App\Routing;

use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Routing\Router as BaseRouter;

class Router extends BaseRouter
{
    public function apiResource($name, $controller, array $options = []): PendingResourceRegistration
    {
        $only = [
            'index',
            'show',
            'create',
            'update',
            'delete',
            'restore',
            // 'deleteMultiple',
            // 'restoreMultiple',
        ];

        $this->resourceParameters([$name => 'model']);

        if (isset($options['except'])) {
            $only = array_diff($only, (array)$options['except']);
        }

        return $this->resource($name, $controller, array_merge([
            'only' => $only,
        ], $options));
    }
}
