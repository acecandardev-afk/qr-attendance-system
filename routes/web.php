<?php

use App\Http\Controllers\Admin\AttendanceAttemptController;
use App\Http\Controllers\Admin\AttendanceSettingsController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Faculty\AttendanceSessionController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
    Route::get('/login', [LoginController::class, 'showLoginForm']);
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

// Authenticated routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
    Route::get('/logout', function () {
        return redirect()->route('dashboard')
            ->with('status', 'For security, please use the Logout button in the header.');
    })->name('logout.get');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        // Users
        Route::post('users/bulk-destroy', [App\Http\Controllers\Admin\UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::resource('users', App\Http\Controllers\Admin\UserController::class);

        // Departments
        Route::post('departments/bulk-destroy', [App\Http\Controllers\Admin\DepartmentController::class, 'bulkDestroy'])->name('departments.bulk-destroy');
        Route::resource('departments', App\Http\Controllers\Admin\DepartmentController::class);

        // Courses
        Route::post('courses/bulk-destroy', [App\Http\Controllers\Admin\CourseController::class, 'bulkDestroy'])->name('courses.bulk-destroy');
        Route::resource('courses', App\Http\Controllers\Admin\CourseController::class);

        // Sections
        Route::post('sections/bulk-destroy', [App\Http\Controllers\Admin\SectionController::class, 'bulkDestroy'])->name('sections.bulk-destroy');
        Route::resource('sections', App\Http\Controllers\Admin\SectionController::class);

        // Schedules
        Route::post('schedules/bulk-destroy', [App\Http\Controllers\Admin\ScheduleController::class, 'bulkDestroy'])->name('schedules.bulk-destroy');
        Route::resource('schedules', App\Http\Controllers\Admin\ScheduleController::class);

        // Enrollments
        Route::post('enrollments/bulk-destroy', [App\Http\Controllers\Admin\EnrollmentController::class, 'bulkDestroy'])->name('enrollments.bulk-destroy');
        Route::resource('enrollments', App\Http\Controllers\Admin\EnrollmentController::class);

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
            Route::get('/student', [App\Http\Controllers\Admin\ReportController::class, 'studentReport'])->name('student');
            Route::get('/section', [App\Http\Controllers\Admin\ReportController::class, 'sectionReport'])->name('section');
            Route::get('/faculty', [App\Http\Controllers\Admin\ReportController::class, 'facultyReport'])->name('faculty');
            Route::get('/trends', [App\Http\Controllers\Admin\ReportController::class, 'trendsReport'])->name('trends');
            Route::post('/export/student', [App\Http\Controllers\Admin\ReportController::class, 'exportStudentCSV'])->name('export.student');
        });

        // Security / Audit
        Route::get('/attendance-attempts', [AttendanceAttemptController::class, 'index'])->name('attendance-attempts.index');

        // Settings
        Route::get('/settings/attendance', [AttendanceSettingsController::class, 'edit'])->name('settings.attendance.edit');
        Route::post('/settings/attendance', [AttendanceSettingsController::class, 'update'])->name('settings.attendance.update');
    });

    // Faculty routes
    Route::middleware('role:faculty')->prefix('faculty')->name('faculty.')->group(function () {
        Route::get('/sessions', [AttendanceSessionController::class, 'index'])->name('sessions.index');
        Route::post('/sessions', [AttendanceSessionController::class, 'store'])->name('sessions.store');
        Route::post('/sessions/ad-hoc', [AttendanceSessionController::class, 'storeAdHoc'])->name('sessions.store.ad-hoc');
        Route::get('/sessions/{session}', [AttendanceSessionController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{session}/close', [AttendanceSessionController::class, 'close'])->name('sessions.close');
        Route::get('/sessions/{session}/status', [AttendanceSessionController::class, 'status'])->name('sessions.status');
        Route::post('/sessions/{session}/attendance/manual', [AttendanceSessionController::class, 'updateManualAttendance'])->name('sessions.attendance.manual');
        Route::post('/sessions/{session}/attendance/manual/bulk', [AttendanceSessionController::class, 'bulkManualAttendance'])->name('sessions.attendance.manual.bulk');

        // Reports
        Route::get('/reports', [App\Http\Controllers\Faculty\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/class', [App\Http\Controllers\Faculty\ReportController::class, 'classReport'])->name('reports.class');

        Route::resource('enrollments', App\Http\Controllers\Faculty\EnrollmentController::class)->except(['show']);
    });

    // Student routes
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/attendance', [App\Http\Controllers\Student\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post(
            '/attendance/scan',
            [App\Http\Controllers\Student\AttendanceController::class, 'scan']
        )->middleware('throttle:30,1')->name('attendance.scan');
        Route::get('/attendance/history', [App\Http\Controllers\Student\AttendanceController::class, 'history'])->name('attendance.history');
    });

});

// Health check (no internal details exposed to clients)
Route::get('/health', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
    } catch (\Throwable $e) {
        report($e);

        return response()->json(['status' => 'unavailable'], 503);
    }

    return response()->json(['status' => 'ok']);
});
