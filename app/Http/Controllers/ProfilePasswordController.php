<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class ProfilePasswordController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.settings.account.edit');
        }

        abort_unless($user->isFaculty() || $user->isStudent(), 403);

        return view('settings.password');
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.settings.account.edit');
        }

        abort_unless($user->isFaculty() || $user->isStudent(), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        return redirect()->route('settings.password.edit')
            ->with('success', 'Your password was updated.');
    }
}
