<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RedirectsMissingAdminRecord;
use App\Http\Controllers\Concerns\ValidatesBulkIds;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    use RedirectsMissingAdminRecord;
    use ValidatesBulkIds;

    public function index(Request $request)
    {
        $query = Course::with('department');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $courses = $query->latest()->paginate(20);
        $departments = Department::active()->get();

        return view('admin.courses.index', compact('courses', 'departments'));
    }

    public function show($course)
    {
        return $this->redirectShowToEditOrIndex(
            Course::class,
            $course,
            'admin.courses.index',
            'admin.courses.edit',
            'That course no longer exists or was removed.',
        );
    }

    public function create()
    {
        $departments = Department::active()->get();

        return view('admin.courses.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'code')->whereNull('deleted_at'),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'units' => 'required|integer|min:1|max:10',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|in:active,inactive',
        ]);

        Course::create($validated);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully!');
    }

    public function edit(Course $course)
    {
        $departments = Department::active()->get();

        return view('admin.courses.edit', compact('course', 'departments'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'code')->whereNull('deleted_at')->ignore($course->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'units' => 'required|integer|min:1|max:10',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|in:active,inactive',
        ]);

        $course->update($validated);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course updated successfully!');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $this->validatedBulkIds($request, 'courses');
        Course::whereIn('id', $ids)->get()->each->delete();

        return back()->with('success', count($ids).' course(s) removed.');
    }
}
