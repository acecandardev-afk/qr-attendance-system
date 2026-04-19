<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * When Caddy sends X-Forwarded-Host (browser was https://LAN:9443), use that for generated
 * URLs so redirects and asset() go to the same host instead of APP_URL (e.g. http://localhost).
 *
 * TrustProxies limits trusted X-Forwarded-* to the proxy (e.g. 127.0.0.1). Host to PHP is still
 * 127.0.0.1:8000 (see Caddyfile).
 */
class UseForwardedHostAsUrlRoot
{
    public function handle(Request $request, Closure $next): Response
    {
        $hostHeader = $request->header('X-Forwarded-Host');
        if (! $hostHeader) {
            return $next($request);
        }

        $hostOnly = strtok($hostHeader, ':') ?: '';
        if (in_array($hostOnly, ['127.0.0.1', 'localhost'], true)) {
            return $next($request);
        }

        $proto = $request->header('X-Forwarded-Proto') ?: 'https';
        $root = rtrim($proto.'://'.$hostHeader, '/');

        URL::forceRootUrl($root);
        URL::forceScheme($proto === 'https' ? 'https' : 'http');

        return $next($request);
    }
}
