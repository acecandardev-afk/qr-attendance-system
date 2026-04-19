<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Support\NameConcatSql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FacultyController extends Controller
{
    public const EMPLOYMENT_STATUSES = ['part_time', 'temporary', 'regular'];

    public static function employmentStatusLabel(?string $value): string
    {
        return match ($value) {
            'part_time' => 'Part-time',
            'temporary' => 'Temporary',
            'regular' => 'Regular',
            default => '—',
        };
    }

    public function index(Request $request)
    {
        $faculties = $this->facultyIndexQuery($request)->paginate(20)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('admin.faculties.index', compact('faculties', 'departments'));
    }

    /**
     * Printable list: all faculty rows matching the same filters as the index (no pagination).
     */
    public function print(Request $request)
    {
        $faculties = $this->facultyIndexQuery($request)->get();

        return view('admin.faculties.print', [
            'faculties' => $faculties,
            'isArchived' => $request->boolean('archived'),
        ]);
    }

    protected function facultyIndexQuery(Request $request): Builder
    {
        $query = User::faculty()->with('department');

        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }

        if ($request->filled('user_id')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->user_id)).'%';
            $query->where('user_id', 'like', $term);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->q)).'%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('middle_name', 'like', $term)
                    ->orWhereRaw(NameConcatSql::firstSpaceLastTrimmed().' like ?', [$term]);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query->orderBy('last_name')->orderBy('first_name');
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();

        return view('admin.faculties.create', compact('departments'));
    }

    public function store(Request $request)
    {
        if (! $request->filled('email')) {
            $request->merge(['email' => null]);
        }

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
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'employment_status' => 'required|in:'.implode(',', self::EMPLOYMENT_STATUSES),
            'account_status' => 'required|in:active,inactive',
        ]);

        $validated['user_id'] = trim($validated['user_id']);
        $validated['role'] = 'faculty';
        $validated['status'] = $validated['account_status'];
        unset($validated['account_status']);

        if (empty($validated['email'])) {
            $validated['email'] = Str::lower($validated['user_id']).'@school.edu';
        } else {
            $validated['email'] = Str::lower(trim($validated['email']));
        }

        $validated['email_verified_at'] = now();

        User::create($validated);

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Faculty member created successfully.');
    }

    public function edit(User $faculty)
    {
        abort_unless($faculty->isFaculty(), 404);
        $departments = Department::orderBy('name')->get();

        return view('admin.faculties.edit', compact('faculty', 'departments'));
    }

    public function update(Request $request, User $faculty)
    {
        abort_unless($faculty->isFaculty(), 404);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'user_id')->whereNull('deleted_at')->ignore($faculty->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($faculty->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'employment_status' => 'required|in:'.implode(',', self::EMPLOYMENT_STATUSES),
            'account_status' => 'required|in:active,inactive',
        ]);

        $validated['user_id'] = trim($validated['user_id']);
        $validated['email'] = Str::lower(trim($validated['email']));
        $validated['status'] = $validated['account_status'];
        unset($validated['account_status']);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $faculty->update($validated);

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Faculty member updated successfully.');
    }

    public function destroy(Request $request, User $faculty)
    {
        abort_unless($faculty->isFaculty(), 404);

        if ($faculty->id === $request->user()->id) {
            return back()->with('error', 'You cannot archive your own account.');
        }

        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $faculty->delete();

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Faculty member archived successfully.');
    }

    public function restore(int $id)
    {
        $faculty = User::onlyTrashed()->where('role', 'faculty')->findOrFail($id);
        $faculty->restore();

        return redirect()->route('admin.faculties.index', ['archived' => 1])
            ->with('success', 'Faculty member restored successfully.');
    }
}
