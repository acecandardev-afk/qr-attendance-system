<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563eb" id="meta-theme-color">
    <title>{{ config('app.name') }} - @yield('title')</title>

    {{-- Light mode only: strip dark class and legacy theme preference --}}
    <script>
        (function () {
            try {
                document.documentElement.classList.remove('dark');
                localStorage.removeItem('theme_preference_v1');
                var meta = document.getElementById('meta-theme-color');
                if (meta) meta.setAttribute('content', '#f8fafc');
            } catch (e) { /* ignore */ }
        })();
    </script>

    <link rel="manifest" href="/manifest.webmanifest">
    {{-- No Google Fonts here: LAN/offline classrooms hang on external CSS until timeout. --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        :root {
            /* School theme */
            --school-primary: #0f3b8c; /* navy */
            --school-accent: #fbbf24;  /* gold */
            --sidebar-width: 18rem;

            --app-bg-start: #f8fafc;
            --app-bg-end: #eef2ff;
            --app-border: rgba(148, 163, 184, 0.22);
            --app-text: #0f172a;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: var(--app-text);
            background:
                radial-gradient(circle at 15% 0%, rgba(15, 59, 140, 0.14), transparent 28%),
                radial-gradient(circle at 85% 100%, rgba(251, 191, 36, 0.14), transparent 30%),
                linear-gradient(180deg, var(--app-bg-start) 0%, var(--app-bg-end) 100%);
            min-height: 100vh;
        }

        /* Sidebar + mobile shell */
        .brand-title {
            letter-spacing: -0.01em;
            line-height: 1.1;
        }

        .app-shell {
            min-height: 100vh;
        }

        .app-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: transparent;
            padding: 1rem 0.75rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 0.72rem;
            padding: 0.62rem 0.72rem;
            font-weight: 700;
            font-size: 0.87rem;
            color: #1e293b;
            white-space: nowrap;
        }

        .sidebar-link:hover {
            background: rgba(15, 59, 140, 0.1);
            color: var(--school-primary);
        }

        .sidebar-link.active {
            background: linear-gradient(135deg, #3b6ff7, #4f46e5);
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.28);
        }

        .app-content {
            min-height: 100vh;
            min-width: 0;
            max-height: 100vh;
            overflow-x: auto;
            overflow-y: auto;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
        }

        /* ── Mobile responsive ── */
        @media (max-width: 767px) {
            .app-shell {
                flex-direction: column;
            }
            .app-sidebar {
                transform: translateX(-100%);
                transition: transform 220ms ease;
                z-index: 50;
            }
            .app-sidebar.open {
                transform: translateX(0);
            }
            .app-content {
                margin-left: 0;
                width: 100%;
            }
            .mobile-overlay {
                display: block;
            }
        }

        .mobile-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.45);
            z-index: 40;
        }

        .mobile-topbar {
            display: none;
        }

        @media (max-width: 767px) {
            .mobile-topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.8rem 1rem;
                background: linear-gradient(90deg, #0f1e3d, #1a3166);
                border-bottom: 1px solid rgba(255,255,255,0.08);
                position: sticky;
                top: 0;
                z-index: 30;
                box-shadow: 0 2px 12px rgba(15,23,42,0.25);
                color: white;
            }
            .mobile-topbar button { color: rgba(255,255,255,0.85); }
            .mobile-topbar button:hover { background: rgba(255,255,255,0.1); }
            .mobile-topbar span { color: white; }
            .mobile-topbar span.text-xs { color: rgba(255,255,255,0.5) !important; font-size: 0.65rem; letter-spacing: 0.1em; }
        }

        .sidebar-panel {
            height: 100%;
            border-radius: 1.15rem;
            background: linear-gradient(160deg, #0f1e3d 0%, #1a3166 60%, #0f3b8c 100%);
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.35);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.07);
        }

        .sidebar-top {
            padding: 1.1rem 1rem 0.9rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-top .brand-title {
            color: #ffffff;
            font-size: 1.05rem;
        }

        .sidebar-top p {
            color: rgba(255,255,255,0.5) !important;
            letter-spacing: 0.08em;
            font-size: 0.7rem;
        }

        .sidebar-menu {
            padding: 0.75rem 0.65rem;
            overflow-y: auto;
            flex: 1;
        }

        .sidebar-link {
            color: rgba(255, 255, 255, 0.75) !important;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }

        .sidebar-link.active {
            background: rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.2), 0 4px 14px rgba(0,0,0,0.2) !important;
        }

        .sidebar-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.85rem;
            background: rgba(0, 0, 0, 0.15);
        }

        .sidebar-bottom p {
            color: rgba(255,255,255,0.85) !important;
        }

        .sidebar-logout {
            width: 100%;
            background: rgba(239, 68, 68, 0.85);
            color: #fff;
            border-radius: 0.7rem;
            padding: 0.6rem 0.72rem;
            font-size: 0.84rem;
            font-weight: 700;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logout:hover {
            background: #ef4444;
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
    <div class="app-shell flex" x-data="{ sidebarOpen: false }">

        <!-- Mobile top bar -->
        <div class="mobile-topbar">
            <button @click="sidebarOpen = true" class="p-1 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none" aria-label="Open menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="font-bold text-slate-800 text-sm truncate mx-2">{{ config('app.name') }}</span>
            <span class="text-xs text-slate-500 uppercase tracking-wider">{{ ucfirst(Auth::user()->role) }}</span>
        </div>

        <!-- Mobile overlay -->
        <div class="mobile-overlay" x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity></div>

        <aside class="app-sidebar fixed inset-y-0 left-0 flex flex-col" :class="{ 'open': sidebarOpen }">
            <div class="sidebar-panel">
                <div class="sidebar-top">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('dashboard', [], false) }}" class="brand-title text-xl font-extrabold text-slate-800 block truncate flex items-center gap-3">
                            <img src="/norsu.webp" alt="Logo" class="w-5 h-5 rounded-full bg-white object-contain">
                            <span class="truncate">{{ config('app.name') }}</span>
                        </a>
                        <button @click="sidebarOpen = false" class="md:hidden p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100" aria-label="Close menu">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-6 h-6">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs uppercase tracking-wider text-slate-500 mt-1">{{ ucfirst(Auth::user()->role) }} Panel</p>
                </div>

                <nav class="sidebar-menu space-y-1" @click="sidebarOpen = false">
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('dashboard', [], false) }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.departments.index', [], false) }}" class="sidebar-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">Departments</a>
                    <a href="{{ route('admin.faculties.index', [], false) }}" class="sidebar-link {{ request()->routeIs('admin.faculties.*') ? 'active' : '' }}">Faculty</a>
                    <a href="{{ route('admin.students.index', [], false) }}" class="sidebar-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">Students</a>
                    <a href="{{ route('admin.settings.account.edit', [], false) }}" class="sidebar-link {{ request()->routeIs('admin.settings.account.*') ? 'active' : '' }}">Settings</a>
                    <a href="{{ route('admin.attendance-attempts.index', [], false) }}" class="sidebar-link {{ request()->routeIs('admin.attendance-attempts.*') ? 'active' : '' }}">Security</a>
                @elseif(Auth::user()->isFaculty())
                    <a href="{{ route('faculty.profile', [], false) }}" class="sidebar-link {{ request()->routeIs('faculty.profile') ? 'active' : '' }}">My Profile</a>
                    <a href="{{ route('faculty.sessions.index', [], false) }}" class="sidebar-link {{ request()->routeIs('faculty.sessions.*', 'faculty.subjects.*', 'faculty.classes.*', 'faculty.settings.class-rules.*', 'faculty.enrollments.*', 'faculty.schedules.students-for-enrollment', 'faculty.schedules.enrollments.bulk') ? 'active' : '' }}">Classes</a>
                    <a href="{{ route('faculty.reports.index', [], false) }}" class="sidebar-link {{ request()->routeIs('faculty.reports.*') ? 'active' : '' }}">History</a>
                @elseif(Auth::user()->isStudent())
                    <a href="{{ route('dashboard', [], false) }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('student.classes.browse', [], false) }}" class="sidebar-link {{ request()->routeIs('student.classes.browse') ? 'active' : '' }}">Classes you can join</a>
                    <a href="{{ route('student.attendance.index', [], false) }}" class="sidebar-link {{ request()->routeIs('student.attendance.index') ? 'active' : '' }}">Scan QR</a>
                    <a href="{{ route('student.attendance.history', [], false) }}" class="sidebar-link {{ request()->routeIs('student.attendance.history') ? 'active' : '' }}">History</a>
                    <a href="{{ route('settings.password.edit', [], false) }}" class="sidebar-link {{ request()->routeIs('settings.password.*') ? 'active' : '' }}">Change password</a>
                @endif
                </nav>

                <div class="sidebar-bottom mt-auto">
                    <p class="text-sm font-semibold text-slate-700 truncate mb-2">{{ Auth::user()->full_name }}</p>
                    <form method="POST" action="{{ route('logout', [], false) }}" class="mt-2">
                        @csrf
                        <button type="submit" class="sidebar-logout">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="app-content flex-1">
    @endauth

    <!-- Global flash toasts (fixed; auto-dismiss after 3.5s). Error > success > status. -->
    @if(session('error') || session('success') || session('status'))
        <div class="fixed inset-x-0 top-4 flex justify-center z-50 px-4 pointer-events-none" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3500)">
            <div class="max-w-lg w-full pointer-events-auto">
                @if(session('error'))
                    <div class="rounded-xl bg-red-100 border border-red-300 text-red-900 px-4 py-3 text-sm shadow-lg flex items-center gap-3">
                        <p class="flex-1 text-center font-medium">{{ session('error') }}</p>
                        <button type="button" class="shrink-0 text-red-700 hover:text-red-900 text-lg leading-none px-1" @click="show = false" aria-label="Dismiss">&times;</button>
                    </div>
                @elseif(session('success'))
                    <div class="rounded-xl bg-green-100 border border-green-300 text-green-900 px-4 py-3 text-sm shadow-lg flex items-center gap-3">
                        <p class="flex-1 text-center font-medium">{{ session('success') }}</p>
                        <button type="button" class="shrink-0 text-green-800 hover:text-green-950 text-lg leading-none px-1" @click="show = false" aria-label="Dismiss">&times;</button>
                    </div>
                @elseif(session('status'))
                    <div class="rounded-xl bg-green-100 border border-green-300 text-green-900 px-4 py-3 text-sm shadow-lg flex items-center gap-3">
                        <p class="flex-1 text-center font-medium">{{ session('status') }}</p>
                        <button type="button" class="shrink-0 text-green-800 hover:text-green-950 text-lg leading-none px-1" @click="show = false" aria-label="Dismiss">&times;</button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="py-6 sm:py-8 px-4 sm:px-6 lg:px-8">
        @if($errors->any() && !session('error'))
            <div class="max-w-3xl mx-auto mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm" role="alert"
                 x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3500)">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
    @auth
        </div>
    </div>
    @endauth

    @auth
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/service-worker.js').catch(function (error) {
                    console.error('Service worker registration failed:', error);
                });
            });
        }
    </script>
    @endauth

    @stack('scripts')
</body>
</html>
