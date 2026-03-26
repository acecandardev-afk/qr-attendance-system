@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-600 via-indigo-600 to-slate-900 relative overflow-hidden px-4">
    <div class="pointer-events-none absolute -left-24 -top-24 w-72 h-72 bg-blue-400/40 blur-3xl rounded-full animate-pulse"></div>
    <div class="pointer-events-none absolute -right-24 -bottom-24 w-80 h-80 bg-indigo-500/30 blur-3xl rounded-full animate-[pulse_8s_ease-in-out_infinite]"></div>

    <div class="relative w-full max-w-md">
        <div class="bg-white/95 backdrop-blur shadow-2xl rounded-2xl px-6 py-8 sm:px-8 sm:py-10">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Reset password</h2>
                <p class="text-gray-500 mt-2 text-sm">Enter your email and we will send a reset link.</p>
            </div>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('email') border-red-500 @enderror"
                        placeholder="you@example.com"
                    >
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Email reset link
                </button>
            </form>

            <p class="text-center text-sm text-gray-600 mt-6">
                <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Back to sign in</a>
            </p>
        </div>
    </div>
</div>
@endsection
