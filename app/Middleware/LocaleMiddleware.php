<?php

namespace App\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $languages = singletons()->system->languages;

        $locale = $request->header('Accept-Language') ?? $languages->main['code'];
        $locale = isset($languages->active[$locale]) ? $locale : $languages->main['code'];

        app()->setLocale($locale);

        return $next($request);
    }
}
