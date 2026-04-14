@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#0f3b8c] via-[#1d4ed8] to-[#0b1220] relative overflow-hidden">
    <!-- Animated background orbs -->
    <div class="pointer-events-none absolute -left-24 -top-24 w-72 h-72 bg-[#fbbf24]/30 blur-3xl rounded-full animate-pulse"></div>
    <div class="pointer-events-none absolute -right-24 -bottom-24 w-80 h-80 bg-[#0f3b8c]/35 blur-3xl rounded-full animate-[pulse_8s_ease-in-out_infinite]"></div>

    <div class="relative max-w-5xl w-full px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center sm:items-stretch gap-8">
        <!-- Left: tagline / landing content -->
        <div class="hidden sm:flex flex-col justify-center text-white space-y-4 sm:w-1/2">
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
        <div class="w-full sm:w-1/2 md:w-[26rem]">
            <div class="bg-white/95 backdrop-blur shadow-2xl rounded-2xl px-6 py-8 sm:px-8 sm:py-10 transform transition-all duration-300 hover:shadow-[0_20px_45px_rgba(15,23,42,0.35)] hover:-translate-y-1">
                <div class="text-center mb-8">
                    <div class="flex justify-center mb-4">
                        <img src="{{ asset('norsu.webp') }}" alt="Logo" class="w-8 h-8 rounded-full bg-white object-contain">
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900">Sign in</h2>
                    <p class="text-gray-500 mt-2 text-sm">Access the NORSU-Guihulngan smart attendance system</p>
                </div>

                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('password') border-red-500 @enderror"
                                placeholder="••••••••"
                            >
                            @error('password')
                                <p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p>
                            @enderror
                        </div>
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
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection