<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function mapLegacyDayToPattern(string $day): string
    {
        return match ($day) {
            'Monday', 'Wednesday', 'Friday' => 'MWF',
            'Tuesday', 'Thursday' => 'TTH',
            'Saturday', 'Sunday' => 'MWF',
            default => $day,
        };
    }

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE schedules MODIFY day_of_week VARCHAR(12) NOT NULL');
        }

        $rows = DB::table('schedules')->select('id', 'day_of_week')->get();
        foreach ($rows as $row) {
            $new = $this->mapLegacyDayToPattern((string) $row->day_of_week);
            DB::table('schedules')->where('id', $row->id)->update(['day_of_week' => $new]);
        }

        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'network_identifier')) {
                $table->dropColumn('network_identifier');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('network_identifier')->nullable();
        });
    }
};
