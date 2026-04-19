<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aligns session cookie "secure" flag with the current request so CSRF works when
 * staff use http://127.0.0.1:8000 on the PC while phones use https://LAN:9443.
 *
 * If SESSION_SECURE_COOKIE is set in .env, that explicit true/false wins (read via
 * config() so it still applies when `php artisan config:cache` was run — env() does not).
 */
class ConfigureSessionSecureCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $explicit = config('session.secure');

        if ($explicit !== null && $explicit !== '') {
            config(['session.secure' => filter_var($explicit, FILTER_VALIDATE_BOOL)]);
        } else {
            $secure = $request->secure()
                || $request->headers->get('X-Forwarded-Proto') === 'https';
            config(['session.secure' => $secure]);
        }

        return $next($request);
    }
}
