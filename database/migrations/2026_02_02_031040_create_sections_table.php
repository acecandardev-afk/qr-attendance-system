<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('e.g., BSIT-3A, BSCS-2B');
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('year_level')->comment('e.g., 1, 2, 3, 4');
            $table->string('semester')->comment('e.g., 1st Sem, 2nd Sem');
            $table->string('school_year')->comment('e.g., 2024-2025');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name', 'school_year', 'semester']);
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
