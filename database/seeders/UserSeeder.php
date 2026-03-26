<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $bsitDept = Department::where('code', 'BSIT')->first();
        $bscsDept = Department::where('code', 'BSCS')->first();

        // Admin User
        User::create([
            'user_id' => 'ADMIN-001',
            'email' => 'admin@school.edu',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'first_name' => 'System',
            'middle_name' => null,
            'last_name' => 'Administrator',
            'department_id' => null,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Faculty Users
        $faculty = [
            [
                'user_id' => 'FAC-2024-001',
                'email' => 'jdoe@school.edu',
                'first_name' => 'John',
                'middle_name' => 'Robert',
                'last_name' => 'Doe',
                'department_id' => $bsitDept->id,
            ],
            [
                'user_id' => 'FAC-2024-002',
                'email' => 'msmith@school.edu',
                'first_name' => 'Mary',
                'middle_name' => 'Jane',
                'last_name' => 'Smith',
                'department_id' => $bsitDept->id,
            ],
            [
                'user_id' => 'FAC-2024-003',
                'email' => 'rjohnson@school.edu',
                'first_name' => 'Robert',
                'middle_name' => 'Lee',
                'last_name' => 'Johnson',
                'department_id' => $bscsDept->id,
            ],
        ];

        foreach ($faculty as $fac) {
            User::create([
                'user_id' => $fac['user_id'],
                'email' => $fac['email'],
                'password' => Hash::make('password'),
                'role' => 'faculty',
                'first_name' => $fac['first_name'],
                'middle_name' => $fac['middle_name'],
                'last_name' => $fac['last_name'],
                'department_id' => $fac['department_id'],
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        // Student Users - BSIT
        $students = [
            [
                'user_id' => '2024-00001',
                'email' => 'student1@school.edu',
                'first_name' => 'Alice',
                'middle_name' => 'Marie',
                'last_name' => 'Williams',
                'department_id' => $bsitDept->id,
            ],
            [
                'user_id' => '2024-00002',
                'email' => 'student2@school.edu',
                'first_name' => 'Bob',
                'middle_name' => 'Andrew',
                'last_name' => 'Brown',
                'department_id' => $bsitDept->id,
            ],
            [
                'user_id' => '2024-00003',
                'email' => 'student3@school.edu',
                'first_name' => 'Charlie',
                'middle_name' => 'David',
                'last_name' => 'Davis',
                'department_id' => $bsitDept->id,
            ],
            [
                'user_id' => '2024-00004',
                'email' => 'student4@school.edu',
                'first_name' => 'Diana',
                'middle_name' => 'Rose',
                'last_name' => 'Miller',
                'department_id' => $bsitDept->id,
            ],
            [
                'user_id' => '2024-00005',
                'email' => 'student5@school.edu',
                'first_name' => 'Edward',
                'middle_name' => 'James',
                'last_name' => 'Wilson',
                'department_id' => $bsitDept->id,
            ],
        ];

        // Student Users - BSCS
        $studentsCS = [
            [
                'user_id' => '2024-00006',
                'email' => 'student6@school.edu',
                'first_name' => 'Fiona',
                'middle_name' => 'Grace',
                'last_name' => 'Moore',
                'department_id' => $bscsDept->id,
            ],
            [
                'user_id' => '2024-00007',
                'email' => 'student7@school.edu',
                'first_name' => 'George',
                'middle_name' => 'Henry',
                'last_name' => 'Taylor',
                'department_id' => $bscsDept->id,
            ],
            [
                'user_id' => '2024-00008',
                'email' => 'student8@school.edu',
                'first_name' => 'Hannah',
                'middle_name' => 'Isabel',
                'last_name' => 'Anderson',
                'department_id' => $bscsDept->id,
            ],
        ];

        foreach (array_merge($students, $studentsCS) as $student) {
            User::create([
                'user_id' => $student['user_id'],
                'email' => $student['email'],
                'password' => Hash::make('password'),
                'role' => 'student',
                'first_name' => $student['first_name'],
                'middle_name' => $student['middle_name'],
                'last_name' => $student['last_name'],
                'department_id' => $student['department_id'],
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }
    }
}