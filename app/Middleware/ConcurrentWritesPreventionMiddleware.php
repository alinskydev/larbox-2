<?php

namespace App\Middleware;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ConcurrentWritesPreventionMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (in_array(app()->environment(), ['local', 'testing'])) {
            return $next($request);
        }

        if (in_array($request->method(), [
            $request::METHOD_POST,
            $request::METHOD_PUT,
            $request::METHOD_PATCH,
            $request::METHOD_DELETE,
        ])) {
            $id = user()?->id ?? $request->ip();
            $path = $request->path();
            $cacheKey = "http_write_lock:$id:$path";

            $lock = Cache::lock($cacheKey, 60);

            if ($lock->get()) {
                try {
                    return $next($request);
                } finally {
                    $lock->release();
                }
            }

            throw new ThrottleRequestsException();
        } else {
            return $next($request);
        }
    }
}
