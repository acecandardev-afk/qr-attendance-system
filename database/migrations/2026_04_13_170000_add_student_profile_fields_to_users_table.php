<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('year_level', 50)->nullable()->after('last_name');
            $table->string('address')->nullable()->after('year_level');
            $table->unsignedSmallInteger('age')->nullable()->after('address');
            $table->date('birthday')->nullable()->after('age');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['year_level', 'address', 'age', 'birthday']);
        });
    }
};
