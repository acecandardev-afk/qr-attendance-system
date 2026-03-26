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
