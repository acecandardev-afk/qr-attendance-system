<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Older or hand-edited SQLite databases may lack user_id while the app expects it.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'user_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('user_id')->nullable();
        });

        $users = DB::table('users')->select('id', 'email')->orderBy('id')->get();

        foreach ($users as $row) {
            $email = (string) ($row->email ?? '');
            $local = ($email !== '' && str_contains($email, '@'))
                ? explode('@', $email, 2)[0]
                : 'user';
            $base = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $local) ?? '');
            if ($base === '') {
                $base = 'USER';
            }
            $candidate = substr($base, 0, 40);
            $final = $candidate;
            $n = 0;
            while (DB::table('users')->where('user_id', $final)->exists()) {
                $n++;
                $final = substr($candidate, 0, 28).'-'.$n;
            }
            DB::table('users')->where('id', $row->id)->update(['user_id' => $final]);
        }
    }

    public function down(): void
    {
        // Do not drop user_id — the application requires this column.
    }
};
