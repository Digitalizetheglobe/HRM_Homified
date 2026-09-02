<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventHtmlCache
{
    /**
     * Prevent browsers, PWAs, and proxies from caching HTML that contains CSRF tokens.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type', '');

        if ($contentType !== '' && !str_contains($contentType, 'text/html')) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
