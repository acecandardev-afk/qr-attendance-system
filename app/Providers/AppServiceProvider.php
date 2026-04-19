<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $appUrl = config('app.url');
        if (is_string($appUrl) && str_starts_with($appUrl, 'https://')) {
            // php artisan serve is HTTP-only on :8000; forcing https breaks http://127.0.0.1:8000 (and LAN :8000).
            $req = ! $this->app->runningInConsole() ? request() : null;
            $plainArtisanServe = $req && ! $req->secure() && str_ends_with($req->getHttpHost(), ':8000');
            if (! $plainArtisanServe) {
                URL::forceScheme('https');
            }
        }

        // Root-relative URLs so @vite works on https://LAN:9443 even if APP_URL is still localhost.
        Vite::createAssetPathsUsing(function (string $path, ?bool $secure = null) {
            return '/'.ltrim(str_replace('\\', '/', $path), '/');
        });

        // public/hot (npm run dev) points @vite at http://127.0.0.1:5173 — blocked as mixed content on HTTPS.
        // Only strip when the request came through Caddy (forwarded host), not plain http://127.0.0.1:8000 + Vite.
        if ($this->app->runningInConsole()) {
            return;
        }

        $request = request();
        if (! $request) {
            return;
        }

        $viaCaddy = $request->header('X-Forwarded-Host')
            && ($request->header('X-Forwarded-Proto') === 'https' || $request->isSecure());

        if ($viaCaddy && File::exists(public_path('hot'))) {
            File::delete(public_path('hot'));
        }
    }
}
