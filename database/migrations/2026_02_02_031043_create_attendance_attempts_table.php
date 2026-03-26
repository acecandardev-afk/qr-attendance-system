<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_token')->nullable()->comment('Token from scanned QR');
            $table->enum('result', ['success', 'expired', 'invalid_token', 'not_enrolled', 'duplicate', 'network_mismatch', 'rate_limited', 'other'])->default('other');
            $table->string('ip_address');
            $table->string('network_identifier')->nullable();
            $table->text('error_message')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
            $table->index(['attendance_session_id', 'result']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_attempts');
    }
};
