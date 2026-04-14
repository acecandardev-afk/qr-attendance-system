@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#0f3b8c] via-[#1d4ed8] to-[#0b1220] relative overflow-hidden">
    <!-- Animated background orbs -->
    <div class="pointer-events-none absolute -left-24 -top-24 w-72 h-72 bg-[#fbbf24]/30 blur-3xl rounded-full animate-pulse"></div>
    <div class="pointer-events-none absolute -right-24 -bottom-24 w-80 h-80 bg-[#0f3b8c]/35 blur-3xl rounded-full animate-[pulse_8s_ease-in-out_infinite]"></div>

    <div class="relative max-w-6xl w-full px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center md:items-stretch gap-12">
        <!-- Left: tagline / landing content -->
        <div class="hidden md:flex flex-col justify-center text-white space-y-4">
            <p class="inline-flex items-center text-xs uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full border border-white/20 backdrop-blur">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 mr-2"></span>
                NORSU-Guihulngan · Real-time QR Attendance
            </p>
            <h1 class="text-4xl lg:text-5xl font-extrabold leading-tight drop-shadow-sm">
                Smart, secure<br />attendance tracking.
            </h1>
            <p class="text-sm lg:text-base text-blue-100 max-w-md">
                Official QR-based attendance system for NORSU-Guihulngan — students scan once, faculty monitor sessions, admins see the bigger picture.
            </p>
        </div>

        <!-- Right: login card -->
        <div class="w-full max-w-none md:max-w-none md:w-96">
            <div class="bg-white/95 backdrop-blur shadow-2xl rounded-2xl px-6 py-10 sm:px-8 sm:py-10 transform transition-all duration-300 hover:shadow-[0_20px_45px_rgba(15,23,42,0.35)] hover:-translate-y-1">
                <div class="text-center mb-8">
                    <div class="flex justify-center mb-4">
                        <img src="{{ asset('norsu.webp') }}" alt="Logo" class="w-24 h-24 sm:w-20 sm:h-20 object-contain">
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Sign in</h2>
                    <p class="text-gray-500 mt-2 text-xs">Access the NORSU-Guihulngan smart attendance system</p>
                </div>

                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="login" class="block text-sm font-medium text-gray-700 mb-2">Username or email</label>
                        <input
                            type="text"
                            name="login"
                            id="login"
                            value="{{ old('login') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('login') border-red-500 @enderror"
                            placeholder="School ID or email address"
                        >
                        @error('login')
                            <p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                required
                                class="w-full pr-12 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('password') border-red-500 @enderror"
                                placeholder="••••••••"
                            >
                            <button type="button" id="toggle-password" class="absolute inset-y-0 right-4 px-4 bg-transparent text-slate-500 hover:text-slate-700 focus:outline-none" aria-label="Toggle password visibility">
                                <svg id="toggle-password-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg id="toggle-password-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 hidden">
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a16.64 16.64 0 0 1-1.67 2.68" />
                                    <path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                    <path d="m1 1 22 22" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="ml-2 text-gray-600">Remember me</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline font-medium">Forgot password? (uses email)</a>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('password');
    const btn = document.getElementById('toggle-password');
    const eye = document.getElementById('toggle-password-eye');
    const eyeOff = document.getElementById('toggle-password-eye-off');

    if (!input || !btn || !eye || !eyeOff) return;

    btn.addEventListener('click', function () {
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        eye.classList.toggle('hidden', isHidden);
        eyeOff.classList.toggle('hidden', !isHidden);
    });
});
</script>
@endpush