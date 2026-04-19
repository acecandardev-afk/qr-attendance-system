<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        // Laravel's enum() on SQLite becomes a CHECK limited to weekday names; app stores MWF/TTH/SAT/SUN.
        Schema::disableForeignKeyConstraints();

        Schema::create('schedules__rebuild', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('users')->cascadeOnDelete();
            $table->string('day_of_week', 12);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['faculty_id', 'status']);
            $table->index(['section_id', 'day_of_week']);
        });

        DB::statement('
            INSERT INTO schedules__rebuild (
                id, course_id, section_id, faculty_id, day_of_week, start_time, end_time, room, status, created_at, updated_at, deleted_at
            )
            SELECT
                id, course_id, section_id, faculty_id, day_of_week, start_time, end_time, room, status, created_at, updated_at, deleted_at
            FROM schedules
        ');

        Schema::drop('schedules');
        Schema::rename('schedules__rebuild', 'schedules');

        $maxId = (int) DB::table('schedules')->max('id');
        if ($maxId > 0) {
            DB::table('sqlite_sequence')->where('name', 'schedules')->delete();
            DB::table('sqlite_sequence')->insert(['name' => 'schedules', 'seq' => $maxId]);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        // Irreversible without losing MWF/TTH/SAT/SUN values that are not weekday names.
    }
};
