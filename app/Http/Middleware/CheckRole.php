<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->to('/');
        }

        $user = Auth::user();

        if (! in_array($user->role, $roles)) {
            if ($request->expectsJson()) {
                abort(403, 'You do not have permission to view this page.');
            }

            return redirect()->to(route('dashboard', [], false))
                ->with('error', 'You were redirected to your dashboard because this page is restricted.');
        }

        return $next($request);
    }
}