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
use Illuminate\Database\LostConnectionDetector;
use Illuminate\Database\QueryException;
use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust all proxies so Laravel generates correct HTTPS URLs behind Railway's reverse proxy
        $middleware->trustProxies(at: '*');
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
                '<p style="margin:0 0 12px;opacity:.9;">This deployment is missing required configuration. In your host&apos;s dashboard (e.g. Railway, Render, or Fly.io), open <strong>Variables</strong> / <strong>Environment</strong>, set the values below, then redeploy.</p>'.
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

        $exceptions->renderable(function (ModelNotFoundException $e, Request $request) use ($adminModelNotFoundRedirects) {
            if ($request->expectsJson()) {
                return null;
            }

            $route = $adminModelNotFoundRedirects[$e->getModel()] ?? null;
            if ($route === null) {
                return null;
            }

            if ($e->getModel() === AttendanceSession::class && ! $request->is('faculty/*')) {
                return null;
            }

            if ($e->getModel() !== AttendanceSession::class && ! $request->is('admin/*')) {
                return null;
            }

            return redirect()->route($route)
                ->with('error', 'This item is no longer available or has been removed.');
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
                'Set APP_KEY (generate locally with: php artisan key:generate --show)',
                'Set APP_ENV=production and APP_DEBUG=false',
                'Redeploy after updating environment variables',
            ]);
        });

        $exceptions->renderable(function (QueryException|\PDOException $e, Request $request) use ($renderSetupRequired, $isDatabaseInfrastructureFailure) {
            if ($request->expectsJson()) {
                return null;
            }

            if (! $isDatabaseInfrastructureFailure($e)) {
                return null;
            }

            return $renderSetupRequired('Database connection failed', [
                'Set DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD',
                'Run migrations against your production database (php artisan migrate --force)',
                'Redeploy after updating environment variables',
            ]);
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
