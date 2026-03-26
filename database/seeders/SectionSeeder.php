<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Department;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $bsitDept = Department::where('code', 'BSIT')->first();
        $bscsDept = Department::where('code', 'BSCS')->first();

        $sections = [
            // BSIT Sections
            [
                'name' => 'BSIT-3A',
                'department_id' => $bsitDept->id,
                'year_level' => '3',
                'semester' => '2nd Sem',
                'school_year' => '2024-2025',
                'status' => 'active',
            ],
            [
                'name' => 'BSIT-3B',
                'department_id' => $bsitDept->id,
                'year_level' => '3',
                'semester' => '2nd Sem',
                'school_year' => '2024-2025',
                'status' => 'active',
            ],

            // BSCS Sections
            [
                'name' => 'BSCS-3A',
                'department_id' => $bscsDept->id,
                'year_level' => '3',
                'semester' => '2nd Sem',
                'school_year' => '2024-2025',
                'status' => 'active',
            ],
        ];

        foreach ($sections as $section) {
            Section::create($section);
        }
    }
}