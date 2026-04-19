<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The old index (section_id, course_id, day_of_week, start_time) allowed two different
     * subjects in the same section to share identical day pattern and start time. Business
     * rules treat a section as busy in that slot regardless of course. Slot conflicts are
     * enforced in application code (ignoring soft-deleted schedules).
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique('unique_section_schedule');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->unique(
                ['section_id', 'course_id', 'day_of_week', 'start_time'],
                'unique_section_schedule'
            );
        });
    }
};
