<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RedirectsMissingAdminRecord;
use App\Http\Controllers\Concerns\ValidatesBulkIds;
use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    use RedirectsMissingAdminRecord;
    use ValidatesBulkIds;

    public function index(Request $request)
    {
        $query = Department::withCount(['users']);

        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }

        $departments = $query->latest()->paginate(20)->withQueryString();

        return view('admin.departments.index', compact('departments'));
    }

    public function show($department)
    {
        return $this->redirectShowToEditOrIndex(
            Department::class,
            $department,
            'admin.departments.index',
            'admin.departments.edit',
            'That department no longer exists or was removed.',
        );
    }

    public function create()
    {
        return view('admin.departments.create');
    }

    public function store(Request $request)
    {
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }
        if ($request->input('courses_number') === '' || $request->input('courses_number') === null) {
            $request->merge(['courses_number' => null]);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'code')->whereNull('deleted_at'),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'courses_number' => 'nullable|integer|min:0|max:999999',
        ]);

        $validated['courses_number'] = $validated['courses_number'] ?? null;

        Department::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'courses_number' => $validated['courses_number'],
            'status' => 'active',
        ]);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }
        if ($request->input('courses_number') === '' || $request->input('courses_number') === null) {
            $request->merge(['courses_number' => null]);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'code')->whereNull('deleted_at')->ignore($department->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'courses_number' => 'nullable|integer|min:0|max:999999',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['courses_number'] = $validated['courses_number'] ?? null;

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Request $request, Department $department)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department archived successfully.');
    }

    public function restore(int $id)
    {
        $department = Department::onlyTrashed()->findOrFail($id);
        $department->restore();

        return redirect()->route('admin.departments.index', ['archived' => 1])
            ->with('success', 'Department restored successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $ids = $this->validatedBulkIds($request, 'departments');
        Department::whereIn('id', $ids)->get()->each->delete();

        return back()->with('success', count($ids).' department(s) archived.');
    }
}
