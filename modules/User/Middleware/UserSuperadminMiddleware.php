<?php

namespace Modules\User\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserSuperadminMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (!user()->getService()->isSuperadmin()) {
            abort(403, __('Данный раздел доступен только главному администратору'));
        }

        return $next($request);
    }
}
