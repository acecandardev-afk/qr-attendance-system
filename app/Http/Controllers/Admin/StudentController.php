<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\StudentExcelImportService;
use App\Support\NameConcatSql;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = User::students()->with('department');

        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }

        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->q)).'%';
            $query->where(function ($q) use ($term) {
                $q->where('user_id', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhereRaw(NameConcatSql::firstSpaceLastTrimmed().' like ?', [$term]);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')->paginate(20)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'departments'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();

        return view('admin.students.create', compact('departments'));
    }

    public function import(Request $request, StudentExcelImportService $importService)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ], [
            'file.required' => 'Choose a spreadsheet file to upload.',
            'file.mimes' => 'The file must be an Excel workbook (.xlsx or .xls).',
        ]);

        $result = $importService->import($request->file('file'));

        $parts = [];
        if ($result['created'] > 0) {
            $parts[] = $result['created'].' student'.($result['created'] === 1 ? '' : 's').' added.';
        }
        if ($result['skipped'] > 0) {
            $parts[] = $result['skipped'].' row'.($result['skipped'] === 1 ? '' : 's').' skipped (already in the system).';
        }
        $summary = $parts !== [] ? implode(' ', $parts) : 'No new students were added.';

        return redirect()
            ->route('admin.students.index')
            ->with('success', $summary)
            ->with('import_errors', array_slice($result['errors'], 0, 30));
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
        $validated['user_id'] = trim($validated['user_id']);

        if (empty($validated['email'])) {
            $validated['email'] = Str::lower($validated['user_id']).'@school.edu';
        } else {
            $validated['email'] = Str::lower(trim($validated['email']));
        }

        $validated['password'] = 'password';
        $validated['email_verified_at'] = now();

        User::create($validated);

        return redirect()->route('admin.students.index')
            ->with('success', 'Student created successfully.');
    }

    public function edit(User $student)
    {
        abort_unless($student->isStudent(), 404);
        $departments = Department::orderBy('name')->get();

        return view('admin.students.edit', compact('student', 'departments'));
    }

    public function update(Request $request, User $student)
    {
        abort_unless($student->isStudent(), 404);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'user_id')->whereNull('deleted_at')->ignore($student->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($student->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
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

        $validated['user_id'] = trim($validated['user_id']);
        $validated['email'] = Str::lower(trim($validated['email']));

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $student->update($validated);

        return redirect()->route('admin.students.index')
            ->with('success', 'Student updated successfully.');
    }

    public function destroy(Request $request, User $student)
    {
        abort_unless($student->isStudent(), 404);

        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $student->delete();

        return redirect()->route('admin.students.index')
            ->with('success', 'Student archived successfully.');
    }

    public function restore(int $id)
    {
        $student = User::onlyTrashed()->where('role', 'student')->findOrFail($id);
        $student->restore();

        return redirect()->route('admin.students.index', ['archived' => 1])
            ->with('success', 'Student restored successfully.');
    }
}
