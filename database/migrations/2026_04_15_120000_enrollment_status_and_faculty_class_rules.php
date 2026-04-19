<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE enrollments MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'enrolled'");
        } elseif ($driver === 'sqlite') {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->string('status', 32)->default('enrolled')->change();
            });
        } else {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->string('status', 32)->default('enrolled')->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('check_in_code_valid_minutes')->nullable()->after('employment_status');
            $table->unsignedSmallInteger('late_after_minutes')->nullable()->after('check_in_code_valid_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['check_in_code_valid_minutes', 'late_after_minutes']);
        });
    }
};
