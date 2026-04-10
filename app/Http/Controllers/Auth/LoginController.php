<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string', 'max:255'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        $user = User::where('user_id', $validated['user_id'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'user_id' => 'The username or password you entered is incorrect.',
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'user_id' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()->route('dashboard');
    }
}
