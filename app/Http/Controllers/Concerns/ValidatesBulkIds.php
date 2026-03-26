<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ValidatesBulkIds
{
    /**
     * @return list<int>
     */
    protected function validatedBulkIds(Request $request, string $table, int $max = 200): array
    {
        return $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:'.$max],
            'ids.*' => ['integer', 'distinct', "exists:{$table},id"],
        ])['ids'];
    }
}
