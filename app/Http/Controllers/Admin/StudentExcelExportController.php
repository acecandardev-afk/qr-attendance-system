<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StudentExcelExportService;
use Illuminate\Http\Request;

class StudentExcelExportController extends Controller
{
    public function __invoke(Request $request, StudentExcelExportService $exporter)
    {
        $data = $request->validate([
            'scope' => 'required|in:all,department,year',
            'department_id' => 'nullable|exists:departments,id',
            'year_level' => 'nullable|string|max:50',
        ]);

        $departmentId = null;
        $yearLevel = null;

        if ($data['scope'] === 'department') {
            $request->validate(['department_id' => 'required|exists:departments,id']);
            $departmentId = (int) $data['department_id'];
        }

        if ($data['scope'] === 'year') {
            $request->validate(['year_level' => 'required|string|max:50']);
            $yearLevel = $data['year_level'];
        }

        return $exporter->export($departmentId, $yearLevel);
    }
}
