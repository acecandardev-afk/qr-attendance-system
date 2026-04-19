<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Cross-database expression for searching a trimmed "first last" full name.
 * SQLite has no CONCAT(); it uses || for string concatenation.
 */
class NameConcatSql
{
    public static function firstSpaceLastTrimmed(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "trim(COALESCE(first_name, '') || ' ' || COALESCE(last_name, ''))",
            default => "trim(concat(coalesce(first_name,''),' ',coalesce(last_name,'')))",
        };
    }
}
