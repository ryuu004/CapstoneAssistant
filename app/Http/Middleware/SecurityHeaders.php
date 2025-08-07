<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', "script-src 'self' https://cdn.jsdelivr.net 'unsafe-eval';");

        return $response;
    }
}
