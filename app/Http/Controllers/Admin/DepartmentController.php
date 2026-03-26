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

    public function index()
    {
        $departments = Department::withCount(['users', 'courses', 'sections'])
            ->latest()
            ->paginate(20);

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
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'code')->whereNull('deleted_at'),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        Department::create($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully!');
    }

    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'code')->whereNull('deleted_at')->ignore($department->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully!');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $this->validatedBulkIds($request, 'departments');
        Department::whereIn('id', $ids)->get()->each->delete();

        return back()->with('success', count($ids).' department(s) removed.');
    }
}
