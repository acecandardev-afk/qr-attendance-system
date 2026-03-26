<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['present', 'late', 'absent', 'excused'])->default('present');
            $table->dateTime('marked_at')->comment('Timestamp when student scanned QR');
            $table->string('ip_address')->nullable();
            $table->string('network_identifier')->nullable()->comment('Captured subnet for validation');
            $table->text('remarks')->nullable()->comment('For excused absences or notes');
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id'], 'unique_session_attendance');
            $table->index(['student_id', 'status']);
            $table->index('marked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};