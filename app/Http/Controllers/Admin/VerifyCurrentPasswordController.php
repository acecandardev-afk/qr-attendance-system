<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VerifyCurrentPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $valid = Hash::check($data['password'], $request->user()->password);

        return response()->json(['valid' => $valid]);
    }
}
