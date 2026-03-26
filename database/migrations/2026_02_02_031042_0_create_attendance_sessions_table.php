<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token')->unique()->comment('Unique token embedded in QR code');
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('expires_at')->comment('10 minutes after started_at');
            $table->dateTime('closed_at')->nullable()->comment('Manual closure by faculty');
            $table->enum('status', ['active', 'closed', 'expired'])->default('active');
            $table->string('qr_code_path')->nullable()->comment('Stored QR image path if needed');
            $table->timestamps();

            $table->index(['schedule_id', 'status']);
            $table->index(['faculty_id', 'started_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
