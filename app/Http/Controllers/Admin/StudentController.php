<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = User::students()->with('department');

        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->q)).'%';
            $query->where(function ($q) use ($term) {
                $q->where('user_id', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhereRaw("trim(concat(coalesce(first_name,''),' ',coalesce(last_name,''))) like ?", [$term]);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')->paginate(20)->withQueryString();
        $departments = Department::active()->orderBy('name')->get();

        return view('admin.students.index', compact('students', 'departments'));
    }

    public function create()
    {
        $departments = Department::active()->orderBy('name')->get();

        return view('admin.students.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'user_id')->whereNull('deleted_at'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'year_level' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'age' => 'nullable|integer|min:1|max:150',
            'birthday' => 'nullable|date',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['role'] = 'student';

        if (empty($validated['email'])) {
            $validated['email'] = strtolower($validated['user_id']).'@school.edu';
        }

        $validated['password'] = Hash::make('password');
        $validated['email_verified_at'] = now();

        User::create($validated);

        return redirect()->route('admin.students.index')
            ->with('success', 'Student created successfully!');
    }
}
