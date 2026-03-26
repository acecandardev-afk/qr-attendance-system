<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $bsitDept = Department::where('code', 'BSIT')->first();
        $bscsDept = Department::where('code', 'BSCS')->first();

        $courses = [
            // BSIT Courses
            [
                'code' => 'IT312',
                'name' => 'Web Development 2',
                'description' => 'Advanced web development using modern frameworks',
                'units' => 3,
                'department_id' => $bsitDept->id,
                'status' => 'active',
            ],
            [
                'code' => 'IT313',
                'name' => 'Database Management Systems',
                'description' => 'Database design, implementation, and administration',
                'units' => 3,
                'department_id' => $bsitDept->id,
                'status' => 'active',
            ],
            [
                'code' => 'IT314',
                'name' => 'Systems Analysis and Design',
                'description' => 'Methods and techniques for systems development',
                'units' => 3,
                'department_id' => $bsitDept->id,
                'status' => 'active',
            ],
            [
                'code' => 'IT315',
                'name' => 'Network Administration',
                'description' => 'Network setup, configuration, and management',
                'units' => 3,
                'department_id' => $bsitDept->id,
                'status' => 'active',
            ],

            // BSCS Courses
            [
                'code' => 'CS301',
                'name' => 'Data Structures and Algorithms',
                'description' => 'Advanced data structures and algorithmic techniques',
                'units' => 3,
                'department_id' => $bscsDept->id,
                'status' => 'active',
            ],
            [
                'code' => 'CS302',
                'name' => 'Theory of Computation',
                'description' => 'Formal languages, automata, and computability',
                'units' => 3,
                'department_id' => $bscsDept->id,
                'status' => 'active',
            ],
            [
                'code' => 'CS303',
                'name' => 'Artificial Intelligence',
                'description' => 'Fundamentals of AI and machine learning',
                'units' => 3,
                'department_id' => $bscsDept->id,
                'status' => 'active',
            ],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}