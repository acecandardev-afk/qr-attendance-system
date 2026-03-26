<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            UserSeeder::class,
            CourseSeeder::class,
            SectionSeeder::class,
            ScheduleSeeder::class,
            EnrollmentSeeder::class,
        ]);
    }
}