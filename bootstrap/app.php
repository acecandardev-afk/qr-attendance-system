<?php

use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Schedule as ScheduleModel;
use App\Models\Section;
use App\Models\User;
use App\Support\AttendanceConfig;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\LostConnectionDetector;
use Illuminate\Database\QueryException;
use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\ViewException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Only trust loopback — Caddy on this PC forwards from 127.0.0.1. Using '*' made every
        // browser the "proxy", so spoofed X-Forwarded-Proto could mark http:// LAN as "secure",
        // session cookies broke on HTTP, and login returned 419 Page Expired.
        $proxyList = env('TRUSTED_PROXIES') ?: '127.0.0.1,::1';
        $middleware->trustProxies(at: array_values(array_filter(array_map('trim', explode(',', $proxyList)))));
        // After TrustProxies: use X-Forwarded-Host from Caddy so redirects use https://LAN:9443
        // instead of APP_URL (e.g. http://localhost). See Caddyfile header_up X-Forwarded-*.
        $middleware->append(\App\Http\Middleware\UseForwardedHostAsUrlRoot::class);
        // After TrustProxies so $request->secure() sees X-Forwarded-Proto from Caddy; before
        // the web group's StartSession (global stack runs first). Appended, not prepended.
        $middleware->append(\App\Http\Middleware\ConfigureSessionSecureCookie::class);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Automatically mark expired attendance sessions, when enabled.
        $schedule->call(function () {
            if (AttendanceConfig::get('auto_close_sessions', false)) {
                app(\App\Services\AttendanceSessionService::class)->markExpiredSessions();
            }
        })->everyMinute()->name('attendance-mark-expired-sessions')->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isStudentScan = fn (Request $request) => $request->is('student/attendance/scan');

        $friendlyMissingDataMessage = 'Something on this page is incomplete or no longer available. Please go back or open another section.';

        $redirectFriendlyMissingData = function (Request $request) use ($friendlyMissingDataMessage, $isStudentScan) {
            if ($isStudentScan($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'We could not complete this action. Please try again or ask your instructor for help.',
                ], 500);
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => $friendlyMissingDataMessage], 500);
            }
            if (Auth::check()) {
                return redirect()->to(route('dashboard', [], false))->with('error', $friendlyMissingDataMessage);
            }

            return redirect()->to('/')->with('error', $friendlyMissingDataMessage);
        };

        $isLikelyNullReferenceError = static function (\Throwable $e): bool {
            $msg = $e->getMessage();
            if ($e instanceof \ErrorException) {
                return (bool) preg_match('/Attempt to read property\s+("\w+"|\w+)\s+on\s+null/i', $msg);
            }
            if ($e instanceof \TypeError) {
                return (bool) preg_match('/Cannot access property|on null/i', $msg)
                    && str_contains($msg, 'null');
            }

            return false;
        };

        $renderSetupRequired = function (string $title, array $bullets, int $status = 503) {
            $items = implode('', array_map(
                fn ($b) => '<li style="margin:6px 0;">'.htmlspecialchars($b, ENT_QUOTES, 'UTF-8').'</li>',
                $bullets
            ));

            return response(
                '<!doctype html><html lang="en"><head><meta charset="utf-8" />'.
                '<meta name="viewport" content="width=device-width,initial-scale=1" />'.
                '<title>Setup required</title></head>'.
                '<body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#0b1220;color:#e2e8f0;margin:0;padding:24px;">'.
                '<div style="max-width:820px;margin:0 auto;border:1px solid rgba(148,163,184,.25);background:rgba(15,23,42,.9);border-radius:16px;padding:18px 18px 14px;">'.
                '<div style="font-size:14px;opacity:.9;margin-bottom:8px;">QR Attendance System</div>'.
                '<h1 style="font-size:22px;margin:0 0 10px;">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h1>'.
                '<p style="margin:0 0 12px;opacity:.9;">The database is not reachable or is not set up yet. On your own machine, check the <strong>.env</strong> file. On a server, check environment variables, then restart the app.</p>'.
                '<ul style="margin:0 0 10px;padding-left:18px;">'.$items.'</ul>'.
                '<p style="margin:0;opacity:.75;font-size:13px;">Once configured, refresh this page.</p>'.
                '</div></body></html>',
                $status
            );
        };

        /** True only for unreachable DB, wrong credentials/host, missing driver, unknown DB, or missing tables (migrations). */
        $isDatabaseInfrastructureFailure = static function (\Throwable $e): bool {
            $detector = new LostConnectionDetector;
            if ($detector->causedByLostConnection($e)) {
                return true;
            }

            $msg = $e->getMessage();
            if (str_contains($msg, 'could not find driver')) {
                return true;
            }
            if (str_contains($msg, 'Unknown database') || preg_match('/SQLSTATE\[HY000\]\s*\[1049\]/', $msg)) {
                return true;
            }
            if (str_contains($msg, 'Base table or view not found') || str_contains($msg, 'no such table:')) {
                return true;
            }

            return false;
        };

        $adminModelNotFoundRedirects = [
            User::class => 'admin.users.index',
            Department::class => 'admin.departments.index',
            Course::class => 'admin.courses.index',
            Section::class => 'admin.sections.index',
            ScheduleModel::class => 'admin.schedules.index',
            Enrollment::class => 'admin.enrollments.index',
            AttendanceSession::class => 'faculty.sessions.index',
        ];

        $exceptions->renderable(function (ModelNotFoundException $e, Request $request) use ($adminModelNotFoundRedirects, $isStudentScan) {
            if ($isStudentScan($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This scan could not be processed. Please try again or ask your instructor for help.',
                ], 404);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The item you requested is not available.',
                ], 404);
            }

            $route = $adminModelNotFoundRedirects[$e->getModel()] ?? null;
            if ($route !== null) {
                if ($e->getModel() === AttendanceSession::class && $request->is('faculty/*')) {
                    return redirect()->route($route)
                        ->with('error', 'This item is no longer available or has been removed.');
                }
                if ($e->getModel() !== AttendanceSession::class && $request->is('admin/*')) {
                    return redirect()->route($route)
                        ->with('error', 'This item is no longer available or has been removed.');
                }
            }

            if (Auth::check()) {
                return redirect()->to(route('dashboard', [], false))
                    ->with('error', 'That page or record is not available. It may have been removed.');
            }

            return redirect()->to('/')
                ->with('error', 'That page is not available.');
        });

        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) use ($isStudentScan) {
            if ($isStudentScan($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This scan could not be processed. Please try again or ask your instructor for help.',
                ], 404);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not found.'], 404);
            }

            if (Auth::check()) {
                return redirect()->to(route('dashboard', [], false))
                    ->with('error', 'That page could not be found.');
            }

            return redirect()->to('/')
                ->with('error', 'That page could not be found.');
        });

        $exceptions->renderable(function (\ErrorException|\TypeError $e, Request $request) use ($redirectFriendlyMissingData, $isLikelyNullReferenceError) {
            if (! $isLikelyNullReferenceError($e)) {
                return null;
            }

            report($e);

            return $redirectFriendlyMissingData($request);
        });

        $exceptions->renderable(function (ViewException $e, Request $request) use ($redirectFriendlyMissingData, $isLikelyNullReferenceError) {
            $prev = $e->getPrevious();
            while ($prev instanceof \Throwable) {
                if ($isLikelyNullReferenceError($prev)) {
                    report($e);

                    return $redirectFriendlyMissingData($request);
                }
                $prev = $prev->getPrevious();
            }

            return null;
        });

        $exceptions->renderable(function (ValidationException $e, Request $request) use ($isStudentScan) {
            if ($isStudentScan($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This scan could not be processed. Please try again or ask your instructor for help.',
                ], 422);
            }

            return null;
        });

        $exceptions->renderable(function (TokenMismatchException $e, Request $request) use ($isStudentScan) {
            if ($isStudentScan($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your session expired. Refresh the page, sign in again, then scan the code.',
                ], 419);
            }

            return null;
        });

        $exceptions->renderable(function (TooManyRequestsHttpException $e, Request $request) use ($isStudentScan) {
            if ($isStudentScan($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are scanning too quickly. Please wait a few seconds and try again.',
                ], 429);
            }

            return null;
        });

        // Production hardening: show friendly setup instructions instead of blank HTTP 500s.
        $exceptions->renderable(function (MissingAppKeyException $e, Request $request) use ($renderSetupRequired) {
            if ($request->expectsJson()) {
                return null;
            }

            return $renderSetupRequired('Application key is missing', [
                'Run: php artisan key:generate — or paste a key from php artisan key:generate --show into APP_KEY in .env',
                'Then run: php artisan config:clear',
                'For production, set APP_ENV=production and APP_DEBUG=false',
            ]);
        });

        $exceptions->renderable(function (QueryException|\PDOException $e, Request $request) use ($renderSetupRequired, $isDatabaseInfrastructureFailure, $redirectFriendlyMissingData, $isStudentScan) {
            if ($isDatabaseInfrastructureFailure($e)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'The database is not available.'], 503);
                }

                return $renderSetupRequired('Database connection failed', [
                    'Create or select a database (SQLite file, MySQL, or PostgreSQL) and set DB_CONNECTION and the matching DB_* values in .env (or DATABASE_URL / DB_URL if you use a single URL).',
                    'Run: php artisan migrate (use --force only in production).',
                    'Ensure APP_KEY is set (php artisan key:generate).',
                ]);
            }

            if ($request->expectsJson()) {
                if ($isStudentScan($request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'We could not complete this action. Please try again or ask your instructor for help.',
                    ], 500);
                }

                return response()->json(['message' => 'We could not load this information.'], 500);
            }

            if (! config('app.debug')) {
                report($e);

                return $redirectFriendlyMissingData($request);
            }

            return null;
        });

        $exceptions->renderable(function (\Throwable $e, Request $request) use ($isStudentScan) {
            if (! $isStudentScan($request) || config('app.debug')) {
                return null;
            }

            if ($e instanceof ValidationException
                || $e instanceof TokenMismatchException
                || $e instanceof TooManyRequestsHttpException) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return null;
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'We could not record attendance right now. Please try again in a moment.',
            ], 500);
        });
    })->create();
