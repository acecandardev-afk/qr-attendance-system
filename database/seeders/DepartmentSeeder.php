<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'code' => 'BSIT',
                'name' => 'Bachelor of Science in Information Technology',
                'description' => 'Information Technology program focusing on software development, networking, and systems analysis',
                'status' => 'active',
            ],
            [
                'code' => 'BSCS',
                'name' => 'Bachelor of Science in Computer Science',
                'description' => 'Computer Science program emphasizing theoretical foundations and algorithm design',
                'status' => 'active',
            ],
        ];
        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}