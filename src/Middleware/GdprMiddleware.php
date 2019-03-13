<?php

namespace Cerpus\Gdpr\Middleware;

use Closure;

class GdprMiddleware
{
    public function handle($request, Closure $next)
    {
        // Authenticate the request here.
        return $next($request)->header('X-Gdpr', 'Gdpr Protocol Initiated');
    }
}
