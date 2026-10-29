<?php

namespace Modules\User\Middleware;

use Illuminate\Http\Request;
use Modules\User\Helpers\RoleHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RoleMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $canAccessRoute = RoleHelper::canAccessRoute(
            user: user(),
            route: request()->route()->getName(),
        );

        return $canAccessRoute ? $next($request) : throw new AccessDeniedHttpException();
    }
}
