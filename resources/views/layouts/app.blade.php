<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563eb">
    <title>{{ config('app.name') }} - @yield('title')</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        :root {
            /* School theme */
            --school-primary: #0f3b8c; /* navy */
            --school-accent: #fbbf24;  /* gold */

            --app-bg-start: #f8fafc;
            --app-bg-end: #eef2ff;
            --app-border: rgba(148, 163, 184, 0.22);
            --app-text: #0f172a;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            color: var(--app-text);
            background:
                radial-gradient(circle at 15% 0%, rgba(15, 59, 140, 0.14), transparent 28%),
                radial-gradient(circle at 85% 100%, rgba(251, 191, 36, 0.14), transparent 30%),
                linear-gradient(180deg, var(--app-bg-start) 0%, var(--app-bg-end) 100%);
            min-height: 100vh;
        }

        /* Header */
        nav.app-nav {
            border-bottom: 1px solid var(--app-border);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
        }

        .brand-title {
            letter-spacing: -0.01em;
            line-height: 1.1;
        }

        nav.app-nav .nav-link {
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            font-weight: 700;
            font-size: 0.85rem;
            color: #0f172a;
        }

        nav.app-nav .nav-link:hover {
            background: rgba(15, 59, 140, 0.08);
            color: var(--school-primary);
        }

        /* Cards and surfaces */
        .surface,
        .bg-white.rounded-lg.shadow,
        .bg-white.rounded-xl.shadow,
        .bg-white.rounded-lg.shadow-lg,
        .bg-white.rounded-lg.shadow-sm {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid var(--app-border);
            box-shadow: 0 12px 30px rgba(2, 6, 23, 0.06);
        }

        /* Fluid layout (container-fluid behavior) */
        .app-fluid .max-w-7xl,
        .app-fluid .max-w-4xl,
        .app-fluid .max-w-3xl,
        .app-fluid .max-w-2xl,
        .app-fluid .max-w-xl {
            max-width: 100% !important;
        }

        .app-fluid .mx-auto {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .app-fluid main.py-8,
        .app-fluid main {
            width: 100%;
        }

        /* Buttons */
        button,
        a,
        input,
        select,
        textarea {
            transition: all 160ms ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(15, 59, 140, 0.18);
        }

        /* Toast modern look */
        .toast-card {
            border-radius: 0.9rem;
            border: 1px solid var(--app-border);
            box-shadow: 0 16px 40px rgba(2, 6, 23, 0.12);
        }
    </style>
</head>
<body class="bg-gray-100 antialiased @auth app-fluid @endauth">
    @auth
    <!-- Navigation Bar -->
    <nav class="app-nav shadow-sm" x-data="{ open: false }">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-3 border-b border-slate-200/70">
                <div class="flex items-center">
                    <a href="{{ route('dashboard') }}" class="brand-title text-2xl font-extrabold text-slate-800 whitespace-nowrap">
                        {{ config('app.name') }}
                    </a>
                </div>

                <div class="hidden sm:flex sm:items-center sm:space-x-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-10 w-10 rounded-xl border border-slate-200/70 bg-white/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onclick="window.toggleTheme && window.toggleTheme()"
                        aria-label="Toggle dark mode"
                        title="Toggle dark mode"
                    >
                        <svg class="h-5 w-5 text-slate-700 dark:hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364-6.364-1.414 1.414M7.05 16.95l-1.414 1.414M16.95 16.95l1.414 1.414M7.05 7.05 5.636 5.636M12 8a4 4 0 100 8 4 4 0 000-8z" />
                        </svg>
                        <svg class="h-5 w-5 text-slate-200 hidden dark:block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z" />
                        </svg>
                    </button>
                    <span class="text-sm text-slate-600">
                        {{ Auth::user()->full_name }}
                        <span class="text-xs text-slate-500">({{ ucfirst(Auth::user()->role) }})</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-semibold">
                            Logout
                        </button>
                    </form>
                </div>

                <!-- Mobile menu button -->
                <button
                    type="button"
                    class="sm:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                    aria-controls="mobile-menu"
                    aria-expanded="false"
                    @click="open = !open"
                >
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Desktop nav links row -->
            <div class="hidden sm:block py-2">
                <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap">
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nav-link inline-flex items-center">Dashboard</a>
                        <a href="{{ route('admin.users.index') }}" class="nav-link inline-flex items-center">Users</a>
                        <a href="{{ route('admin.departments.index') }}" class="nav-link inline-flex items-center">Departments</a>
                        <a href="{{ route('admin.courses.index') }}" class="nav-link inline-flex items-center">Courses</a>
                        <a href="{{ route('admin.sections.index') }}" class="nav-link inline-flex items-center">Sections</a>
                        <a href="{{ route('admin.schedules.index') }}" class="nav-link inline-flex items-center">Schedules</a>
                        <a href="{{ route('admin.enrollments.index') }}" class="nav-link inline-flex items-center">Enrollments</a>
                        <a href="{{ route('admin.reports.index') }}" class="nav-link inline-flex items-center">Reports</a>
                        <a href="{{ route('admin.settings.attendance.edit') }}" class="nav-link inline-flex items-center">Settings</a>
                        <a href="{{ route('admin.attendance-attempts.index') }}" class="nav-link inline-flex items-center">Security</a>
                    @elseif(Auth::user()->isFaculty())
                        <a href="{{ route('dashboard') }}" class="nav-link inline-flex items-center">Dashboard</a>
                        <a href="{{ route('faculty.sessions.index') }}" class="nav-link inline-flex items-center">Sessions</a>
                        <a href="{{ route('faculty.enrollments.index') }}" class="nav-link inline-flex items-center">Enrollments</a>
                        <a href="{{ route('faculty.reports.index') }}" class="nav-link inline-flex items-center">Reports</a>
                    @elseif(Auth::user()->isStudent())
                        <a href="{{ route('dashboard') }}" class="nav-link inline-flex items-center">Dashboard</a>
                        <a href="{{ route('student.attendance.index') }}" class="nav-link inline-flex items-center">Scan QR</a>
                        <a href="{{ route('student.attendance.history') }}" class="nav-link inline-flex items-center">History</a>
                    @endif
                </div>
            </div>

            <!-- Mobile nav -->
            <div class="sm:hidden" id="mobile-menu" x-show="open" x-cloak>
                <div class="pt-2 pb-3 space-y-1 border-t border-gray-200">
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Dashboard</a>
                        <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Users</a>
                        <a href="{{ route('admin.departments.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Departments</a>
                        <a href="{{ route('admin.courses.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Courses</a>
                        <a href="{{ route('admin.sections.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Sections</a>
                        <a href="{{ route('admin.schedules.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Schedules</a>
                        <a href="{{ route('admin.enrollments.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Enrollments</a>
                        <a href="{{ route('admin.reports.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Reports</a>
                        <a href="{{ route('admin.settings.attendance.edit') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Settings</a>
                        <a href="{{ route('admin.attendance-attempts.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Security</a>
                    @elseif(Auth::user()->isFaculty())
                        <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Dashboard</a>
                        <a href="{{ route('faculty.sessions.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Sessions</a>
                        <a href="{{ route('faculty.enrollments.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Enrollments</a>
                        <a href="{{ route('faculty.reports.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Reports</a>
                    @elseif(Auth::user()->isStudent())
                        <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Dashboard</a>
                        <a href="{{ route('student.attendance.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">Scan QR</a>
                        <a href="{{ route('student.attendance.history') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100">History</a>
                    @endif
                    <div class="border-t border-gray-200 mt-2 pt-2 flex items-center justify-between px-3 gap-2">
                        <span class="text-sm text-gray-600">
                            {{ Auth::user()->full_name }}
                            <span class="text-xs text-gray-500">({{ ucfirst(Auth::user()->role) }})</span>
                        </span>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-slate-200/70 bg-white/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onclick="window.toggleTheme && window.toggleTheme()"
                            aria-label="Toggle dark mode"
                            title="Toggle dark mode"
                        >
                            <svg class="h-5 w-5 text-slate-700 dark:hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364-6.364-1.414 1.414M7.05 16.95l-1.414 1.414M16.95 16.95l1.414 1.414M7.05 7.05 5.636 5.636M12 8a4 4 0 100 8 4 4 0 000-8z" />
                            </svg>
                            <svg class="h-5 w-5 text-slate-200 hidden dark:block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z" />
                            </svg>
                        </button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <!-- Global flash messages -->
    @if(session('success') || session('error') || session('status'))
        <div class="fixed inset-x-0 top-4 flex justify-center z-50" x-data="{ show: true }" x-show="show" x-transition>
            <div class="max-w-md w-full mx-4">
                @if(session('success'))
                    <div class="mb-2 rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3 text-sm flex justify-between items-start">
                        <div>{{ session('success') }}</div>
                        <button type="button" class="ml-3 text-green-700" @click="show = false">&times;</button>
                    </div>
                @endif
                @if(session('status'))
                    <div class="mb-2 rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3 text-sm flex justify-between items-start">
                        <div>{{ session('status') }}</div>
                        <button type="button" class="ml-3 text-green-700" @click="show = false">&times;</button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-2 rounded-lg bg-red-100 border border-red-300 text-red-800 px-4 py-3 text-sm flex justify-between items-start">
                        <div>{{ session('error') }}</div>
                        <button type="button" class="ml-3 text-red-700" @click="show = false">&times;</button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="py-6 sm:py-8">
        @yield('content')
    </main>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/service-worker.js').catch(function (error) {
                    console.error('Service worker registration failed:', error);
                });
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
