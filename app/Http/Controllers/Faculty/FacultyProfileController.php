<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FacultyProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        return view('faculty.profile.show', compact('user'));
    }
}
