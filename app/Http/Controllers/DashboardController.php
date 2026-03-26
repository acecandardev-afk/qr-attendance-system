<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Route to appropriate dashboard based on role
        return match ($user->role) {
            'admin' => view('dashboard.admin', compact('user')),
            'faculty' => view('dashboard.faculty', compact('user')),
            'student' => view('dashboard.student', compact('user')),
            default => abort(403, 'You do not have permission to open this page.'),
        };
    }
}