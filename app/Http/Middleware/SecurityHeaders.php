<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (App::environment('production')) {
            // Production CSP (strict and production-safe)
            $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com; connect-src 'self' https://generativelanguage.googleapis.com; object-src 'none'; frame-ancestors 'self'; form-action 'self'; report-uri /csp-report;");
        }

        return $next($request);

        return $response;
    }
}
