<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $bsit3a = Section::where('name', 'BSIT-3A')->first();
        $bsit3b = Section::where('name', 'BSIT-3B')->first();
        $bscs3a = Section::where('name', 'BSCS-3A')->first();

        // Enroll BSIT students in BSIT-3A
        $bsitStudents = User::students()
            ->where('user_id', 'like', '2024-0000%')
            ->whereIn('user_id', ['2024-00001', '2024-00002', '2024-00003'])
            ->get();

        foreach ($bsitStudents as $student) {
            Enrollment::create([
                'student_id' => $student->id,
                'section_id' => $bsit3a->id,
                'school_year' => '2024-2025',
                'semester' => '2nd Sem',
                'status' => 'enrolled',
            ]);
        }

        // Enroll remaining BSIT students in BSIT-3B
        $bsitStudentsB = User::students()
            ->whereIn('user_id', ['2024-00004', '2024-00005'])
            ->get();

        foreach ($bsitStudentsB as $student) {
            Enrollment::create([
                'student_id' => $student->id,
                'section_id' => $bsit3b->id,
                'school_year' => '2024-2025',
                'semester' => '2nd Sem',
                'status' => 'enrolled',
            ]);
        }

        // Enroll BSCS students in BSCS-3A
        $bscsStudents = User::students()
            ->whereIn('user_id', ['2024-00006', '2024-00007', '2024-00008'])
            ->get();

        foreach ($bscsStudents as $student) {
            Enrollment::create([
                'student_id' => $student->id,
                'section_id' => $bscs3a->id,
                'school_year' => '2024-2025',
                'semester' => '2nd Sem',
                'status' => 'enrolled',
            ]);
        }
    }
}