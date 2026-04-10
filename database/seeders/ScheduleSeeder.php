<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $webDev = Course::where('code', 'IT312')->first();
        $dbms = Course::where('code', 'IT313')->first();
        $sad = Course::where('code', 'IT314')->first();
        $network = Course::where('code', 'IT315')->first();

        $dataStructures = Course::where('code', 'CS301')->first();
        $theoryComp = Course::where('code', 'CS302')->first();

        $bsit3a = Section::where('name', 'BSIT-3A')->first();
        $bsit3b = Section::where('name', 'BSIT-3B')->first();
        $bscs3a = Section::where('name', 'BSCS-3A')->first();

        $faculty1 = User::where('user_id', 'FAC-2024-001')->first();
        $faculty2 = User::where('user_id', 'FAC-2024-002')->first();
        $faculty3 = User::where('user_id', 'FAC-2024-003')->first();

        $schedules = [
            [
                'course_id' => $webDev->id,
                'section_id' => $bsit3a->id,
                'faculty_id' => $faculty1->id,
                'day_of_week' => 'MWF',
                'start_time' => '08:00:00',
                'end_time' => '11:00:00',
                'room' => 'IT-LAB-301',
                'status' => 'active',
            ],
            [
                'course_id' => $dbms->id,
                'section_id' => $bsit3a->id,
                'faculty_id' => $faculty1->id,
                'day_of_week' => 'TTH',
                'start_time' => '08:00:00',
                'end_time' => '11:00:00',
                'room' => 'IT-LAB-301',
                'status' => 'active',
            ],
            [
                'course_id' => $sad->id,
                'section_id' => $bsit3a->id,
                'faculty_id' => $faculty2->id,
                'day_of_week' => 'TTH',
                'start_time' => '13:00:00',
                'end_time' => '16:00:00',
                'room' => 'IT-LAB-302',
                'status' => 'active',
            ],
            [
                'course_id' => $network->id,
                'section_id' => $bsit3a->id,
                'faculty_id' => $faculty2->id,
                'day_of_week' => 'F',
                'start_time' => '08:00:00',
                'end_time' => '11:00:00',
                'room' => 'IT-LAB-302',
                'status' => 'active',
            ],

            [
                'course_id' => $webDev->id,
                'section_id' => $bsit3b->id,
                'faculty_id' => $faculty1->id,
                'day_of_week' => 'TTH',
                'start_time' => '08:00:00',
                'end_time' => '11:00:00',
                'room' => 'IT-LAB-301',
                'status' => 'active',
            ],
            [
                'course_id' => $dbms->id,
                'section_id' => $bsit3b->id,
                'faculty_id' => $faculty2->id,
                'day_of_week' => 'TTH',
                'start_time' => '13:00:00',
                'end_time' => '16:00:00',
                'room' => 'IT-LAB-302',
                'status' => 'active',
            ],

            [
                'course_id' => $dataStructures->id,
                'section_id' => $bscs3a->id,
                'faculty_id' => $faculty3->id,
                'day_of_week' => 'MWF',
                'start_time' => '13:00:00',
                'end_time' => '16:00:00',
                'room' => 'CS-LAB-201',
                'status' => 'active',
            ],
            [
                'course_id' => $theoryComp->id,
                'section_id' => $bscs3a->id,
                'faculty_id' => $faculty3->id,
                'day_of_week' => 'TTH',
                'start_time' => '13:00:00',
                'end_time' => '16:00:00',
                'room' => 'CS-LAB-201',
                'status' => 'active',
            ],
        ];

        foreach ($schedules as $schedule) {
            Schedule::create($schedule);
        }
    }
}
