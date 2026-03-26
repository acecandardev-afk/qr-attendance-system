<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Departments: allow reusing codes after soft delete
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_code_unique');
            $table->unique(['code', 'deleted_at'], 'departments_code_deleted_at_unique');
        });

        // Courses: allow reusing codes after soft delete
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique('courses_code_unique');
            $table->unique(['code', 'deleted_at'], 'courses_code_deleted_at_unique');
        });

        // Users: allow reusing IDs/emails after soft delete (admin can recreate accounts)
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_user_id_unique');
            $table->dropUnique('users_email_unique');
            $table->unique(['user_id', 'deleted_at'], 'users_user_id_deleted_at_unique');
            $table->unique(['email', 'deleted_at'], 'users_email_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_user_id_deleted_at_unique');
            $table->dropUnique('users_email_deleted_at_unique');
            $table->unique('user_id', 'users_user_id_unique');
            $table->unique('email', 'users_email_unique');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique('courses_code_deleted_at_unique');
            $table->unique('code', 'courses_code_unique');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_code_deleted_at_unique');
            $table->unique('code', 'departments_code_unique');
        });
    }
};

