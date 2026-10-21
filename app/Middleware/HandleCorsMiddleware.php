<?php

namespace App\Middleware;

use Illuminate\Http\Middleware\HandleCors;

class HandleCorsMiddleware extends HandleCors
{
    // public function handle($request, \Closure $next)
    // {
    //     $cors = $this->container['config']->get('cors', []);
    //     $this->container['config']->set('cors', $cors);

    //     return parent::handle($request, $next);
    // }
}
