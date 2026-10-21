<?php

namespace App\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceModeMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        throw new \Exception('Ведутся технические работы. Данный раздел временно недоступен.');
    }
}
