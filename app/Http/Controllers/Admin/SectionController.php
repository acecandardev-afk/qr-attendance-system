<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RedirectsMissingAdminRecord;
use App\Http\Controllers\Concerns\ValidatesBulkIds;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    use RedirectsMissingAdminRecord;
    use ValidatesBulkIds;

    public function index(Request $request)
    {
        $query = Section::with('department');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        $sections = $query->latest()->paginate(20);
        $departments = Department::active()->get();

        return view('admin.sections.index', compact('sections', 'departments'));
    }

    public function show($section)
    {
        return $this->redirectShowToEditOrIndex(
            Section::class,
            $section,
            'admin.sections.index',
            'admin.sections.edit',
            'That section no longer exists or was removed.',
        );
    }

    public function create()
    {
        $departments = Department::active()->get();

        return view('admin.sections.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'year_level' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'school_year' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        Section::create($validated);

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section created successfully!');
    }

    public function edit(Section $section)
    {
        $departments = Department::active()->get();

        return view('admin.sections.edit', compact('section', 'departments'));
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'year_level' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'school_year' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $section->update($validated);

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section updated successfully!');
    }

    public function destroy(Section $section)
    {
        $section->delete();

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $this->validatedBulkIds($request, 'sections');
        Section::whereIn('id', $ids)->get()->each->delete();

        return back()->with('success', count($ids).' section(s) removed.');
    }
}
