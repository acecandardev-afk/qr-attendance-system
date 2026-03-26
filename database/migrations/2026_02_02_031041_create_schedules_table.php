<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('users')->cascadeOnDelete();
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->string('network_identifier')->nullable()->comment('Subnet or classroom network ID for IP validation');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['faculty_id', 'status']);
            $table->index(['section_id', 'day_of_week']);
            $table->unique(['section_id', 'course_id', 'day_of_week', 'start_time'], 'unique_section_schedule');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};