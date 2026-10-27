<?php

namespace App\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey || !$request->isMethod($request::METHOD_POST)) {
            return $next($request);
        }

        $id = user()->id;
        $path = $request->path();
        $idempotencyKey = md5($idempotencyKey);
        $cacheKey = "http_idempotency:$id:$path:$idempotencyKey";

        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached['data'], $cached['status'], ['Idempotent-Replayed' => 'true']);
        }

        $response = $next($request);

        if ($response instanceof JsonResponse && $response->isSuccessful()) {
            Cache::set($cacheKey, [
                'data' => $response->getData(true),
                'status' => $response->getStatusCode(),
            ], 600);
        }

        return $response;
    }
}
