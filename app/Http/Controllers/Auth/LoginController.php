<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->to(route('dashboard', [], false));
        }

        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        $login = trim($validated['login']);
        $loginLower = Str::lower($login);

        $hasUserIdColumn = Schema::hasColumn((new User)->getTable(), 'user_id');

        $user = null;
        if ($hasUserIdColumn) {
            $user = User::query()
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(user_id)) = ?', [$loginLower])
                ->first();
        }
        if (! $user) {
            $user = User::query()
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(email)) = ?', [$loginLower])
                ->first();
        }

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'The username or password you entered is incorrect.',
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'login' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()->to(route('dashboard', [], false));
    }
}
