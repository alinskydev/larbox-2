<?php

namespace Modules\User\Middleware;

use Illuminate\Http\Request;
use Modules\User\Helpers\RoleHelper;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $canAccessRoute = RoleHelper::canAccessRoute(
            user: user(),
            route: request()->route()->getName(),
        );

        return $canAccessRoute ? $next($request) : abort(403, __('У вас нет доступа для совершения данного действия'));
    }
}
