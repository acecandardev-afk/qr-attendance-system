<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexNames = collect(Schema::getIndexes('enrollments'))->pluck('name')->all();

        Schema::table('enrollments', function (Blueprint $table) use ($indexNames) {
            // MySQL may refuse to drop the composite unique while it backs FK lookups; add dedicated indexes first.
            if (! in_array('enrollments_student_id_index', $indexNames, true)) {
                $table->index('student_id', 'enrollments_student_id_index');
            }
            if (! in_array('enrollments_section_id_index', $indexNames, true)) {
                $table->index('section_id', 'enrollments_section_id_index');
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique('unique_enrollment');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['student_id', 'section_id', 'school_year', 'semester'], 'unique_enrollment');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('enrollments_student_id_index');
            $table->dropIndex('enrollments_section_id_index');
        });
    }
};
