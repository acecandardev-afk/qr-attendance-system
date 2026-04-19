<?php

use App\Http\Controllers\Admin\AccountSettingsController;
use App\Http\Controllers\Admin\AttendanceAttemptController;
use App\Http\Controllers\Admin\AttendanceSettingsController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\StudentExcelExportController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Faculty\AttendanceSessionController;
use App\Http\Controllers\ProfilePasswordController;
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
        return redirect()->to(route('dashboard', [], false))
            ->with('status', 'For security, please use the Logout button in the header.');
    })->name('logout.get');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/settings/password', [ProfilePasswordController::class, 'edit'])->name('settings.password.edit');
    Route::put('/settings/password', [ProfilePasswordController::class, 'update'])->name('settings.password.update');

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        // Departments
        Route::post('departments/bulk-destroy', [App\Http\Controllers\Admin\DepartmentController::class, 'bulkDestroy'])->name('departments.bulk-destroy');
        Route::post('departments/{id}/restore', [App\Http\Controllers\Admin\DepartmentController::class, 'restore'])->name('departments.restore');
        Route::resource('departments', App\Http\Controllers\Admin\DepartmentController::class);

        // Faculties (faculty users)
        Route::get('faculties/print', [FacultyController::class, 'print'])->name('faculties.print');
        Route::post('faculties/{id}/restore', [FacultyController::class, 'restore'])->name('faculties.restore');
        Route::resource('faculties', FacultyController::class)
            ->parameters(['faculties' => 'faculty'])
            ->except(['show']);

        // Students
        Route::post('students/{id}/restore', [App\Http\Controllers\Admin\StudentController::class, 'restore'])->name('students.restore');
        Route::get('students', [App\Http\Controllers\Admin\StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [App\Http\Controllers\Admin\StudentController::class, 'create'])->name('students.create');
        Route::post('students/import', [App\Http\Controllers\Admin\StudentController::class, 'import'])
            ->middleware('throttle:15,1')
            ->name('students.import');
        Route::post('students', [App\Http\Controllers\Admin\StudentController::class, 'store'])->name('students.store');
        Route::get('students/{student}/edit', [App\Http\Controllers\Admin\StudentController::class, 'edit'])->name('students.edit');
        Route::put('students/{student}', [App\Http\Controllers\Admin\StudentController::class, 'update'])->name('students.update');
        Route::delete('students/{student}', [App\Http\Controllers\Admin\StudentController::class, 'destroy'])->name('students.destroy');

        // Student list Excel export
        Route::get('reports/export/students', function () {
            $yearLevels = \App\Models\User::students()
                ->whereNotNull('year_level')
                ->where('year_level', '!=', '')
                ->distinct()
                ->orderBy('year_level')
                ->pluck('year_level');

            return view('admin.reports.students-excel', [
                'departments' => \App\Models\Department::orderBy('name')->get(),
                'yearLevels' => $yearLevels,
            ]);
        })->name('reports.students-export');
        Route::get('reports/students-excel', StudentExcelExportController::class)->name('reports.students-excel');

        // Security / Audit
        Route::get('/attendance-attempts', [AttendanceAttemptController::class, 'index'])->name('attendance-attempts.index');

        // Settings (account + attendance)
        Route::get('/settings/account', [AccountSettingsController::class, 'edit'])->name('settings.account.edit');
        Route::put('/settings/account/password', [AccountSettingsController::class, 'updatePassword'])->name('settings.account.password');
        Route::get('/settings/attendance', [AttendanceSettingsController::class, 'edit'])->name('settings.attendance.edit');
        Route::post('/settings/attendance', [AttendanceSettingsController::class, 'update'])->name('settings.attendance.update');

        Route::post('verify-current-password', App\Http\Controllers\Admin\VerifyCurrentPasswordController::class)
            ->middleware('throttle:30,1')
            ->name('verify-current-password');

        // Legacy admin (subjects, schedules, etc.) — not shown in simplified sidebar
        Route::post('users/bulk-destroy', [App\Http\Controllers\Admin\UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::resource('users', App\Http\Controllers\Admin\UserController::class);
        Route::post('courses/bulk-destroy', [App\Http\Controllers\Admin\CourseController::class, 'bulkDestroy'])->name('courses.bulk-destroy');
        Route::resource('courses', App\Http\Controllers\Admin\CourseController::class);
        Route::post('sections/bulk-destroy', [App\Http\Controllers\Admin\SectionController::class, 'bulkDestroy'])->name('sections.bulk-destroy');
        Route::resource('sections', App\Http\Controllers\Admin\SectionController::class);
        Route::post('schedules/bulk-destroy', [App\Http\Controllers\Admin\ScheduleController::class, 'bulkDestroy'])->name('schedules.bulk-destroy');
        Route::resource('schedules', App\Http\Controllers\Admin\ScheduleController::class);
        Route::post('enrollments/bulk-destroy', [App\Http\Controllers\Admin\EnrollmentController::class, 'bulkDestroy'])->name('enrollments.bulk-destroy');
        Route::resource('enrollments', App\Http\Controllers\Admin\EnrollmentController::class);

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
            Route::get('/daily-print', [App\Http\Controllers\Admin\ReportController::class, 'dailyPrint'])->name('daily-print');
            Route::get('/daily-export', [App\Http\Controllers\Admin\ReportController::class, 'exportDailyCSV'])->name('export.daily');
            Route::get('/student', [App\Http\Controllers\Admin\ReportController::class, 'studentReport'])->name('student');
            Route::get('/section', [App\Http\Controllers\Admin\ReportController::class, 'sectionReport'])->name('section');
            Route::get('/faculty', [App\Http\Controllers\Admin\ReportController::class, 'facultyReport'])->name('faculty');
            Route::get('/trends', [App\Http\Controllers\Admin\ReportController::class, 'trendsReport'])->name('trends');
            Route::post('/export/student', [App\Http\Controllers\Admin\ReportController::class, 'exportStudentCSV'])->name('export.student');
            Route::get('/export/section', [App\Http\Controllers\Admin\ReportController::class, 'exportSectionCSV'])->name('export.section');
            Route::get('/export/faculty', [App\Http\Controllers\Admin\ReportController::class, 'exportFacultyCSV'])->name('export.faculty');
            Route::get('/export/trends', [App\Http\Controllers\Admin\ReportController::class, 'exportTrendsCSV'])->name('export.trends');
        });
    });

    // Faculty routes
    Route::middleware('role:faculty')->prefix('faculty')->name('faculty.')->group(function () {
        Route::get('/profile', [App\Http\Controllers\Faculty\FacultyProfileController::class, 'show'])->name('profile');
        Route::get('/schedules/{schedule}/students-for-enrollment', [App\Http\Controllers\Faculty\ScheduleBulkEnrollmentController::class, 'candidates'])
            ->name('schedules.students-for-enrollment');
        Route::post('/schedules/{schedule}/enrollments/bulk', [App\Http\Controllers\Faculty\ScheduleBulkEnrollmentController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('schedules.enrollments.bulk');

        Route::post('/subjects', [App\Http\Controllers\Faculty\FacultySubjectController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('subjects.store');
        Route::get('/subjects/{schedule}/edit', [App\Http\Controllers\Faculty\FacultySubjectController::class, 'edit'])
            ->name('subjects.edit');
        Route::put('/subjects/{schedule}', [App\Http\Controllers\Faculty\FacultySubjectController::class, 'update'])
            ->middleware('throttle:30,1')
            ->name('subjects.update');
        Route::delete('/subjects/{schedule}', [App\Http\Controllers\Faculty\FacultySubjectController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('subjects.destroy');

        Route::get('/sessions', [AttendanceSessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/today-data', [AttendanceSessionController::class, 'todayData'])
            ->middleware('throttle:120,1')
            ->name('sessions.today-data');
        Route::post('/sessions', [AttendanceSessionController::class, 'store'])->name('sessions.store');
        Route::get('/sessions/{session}/qr.svg', [AttendanceSessionController::class, 'qrSvg'])->name('sessions.qr-svg');
        Route::get('/sessions/{session}', [AttendanceSessionController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{session}/close', [AttendanceSessionController::class, 'close'])->name('sessions.close');
        Route::get('/sessions/{session}/status', [AttendanceSessionController::class, 'status'])->name('sessions.status');

        // Reports
        Route::get('/reports', [App\Http\Controllers\Faculty\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [App\Http\Controllers\Faculty\ReportController::class, 'exportMyReportCSV'])->name('reports.export');
        Route::get('/reports/class', [App\Http\Controllers\Faculty\ReportController::class, 'classReport'])->name('reports.class');
        Route::get('/reports/class/export', [App\Http\Controllers\Faculty\ReportController::class, 'exportClassReportCSV'])->name('reports.class.export');

        Route::get('/settings/class-rules', [App\Http\Controllers\Faculty\ClassRulesController::class, 'edit'])->name('settings.class-rules.edit');
        Route::put('/settings/class-rules', [App\Http\Controllers\Faculty\ClassRulesController::class, 'update'])->name('settings.class-rules.update');

        Route::get('/classes/{schedule}/roster', [App\Http\Controllers\Faculty\ClassRosterController::class, 'show'])->name('classes.roster');

        Route::post('/enrollments/{enrollment}/approve', [App\Http\Controllers\Faculty\EnrollmentController::class, 'approve'])->name('enrollments.approve');
        Route::post('/enrollments/{enrollment}/decline', [App\Http\Controllers\Faculty\EnrollmentController::class, 'decline'])->name('enrollments.decline');
        Route::resource('enrollments', App\Http\Controllers\Faculty\EnrollmentController::class)->except(['show', 'create', 'store']);
    });

    // Student routes
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/classes/browse', [DashboardController::class, 'studentBrowseClasses'])->name('classes.browse');
        Route::get('/attendance', [App\Http\Controllers\Student\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post(
            '/attendance/scan',
            [App\Http\Controllers\Student\AttendanceController::class, 'scan']
        )->middleware('throttle:30,1')->name('attendance.scan');
        Route::get('/attendance/history', [App\Http\Controllers\Student\AttendanceController::class, 'history'])->name('attendance.history');

        Route::post('/classes/join', [App\Http\Controllers\Student\EnrollmentRequestController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('classes.join');
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
