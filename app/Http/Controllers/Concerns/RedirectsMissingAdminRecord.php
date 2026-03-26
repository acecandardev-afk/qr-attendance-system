<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

trait RedirectsMissingAdminRecord
{
    /**
     * Resource "show" URLs: missing or invalid IDs go to the index; existing records redirect to edit.
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function redirectShowToEditOrIndex(
        string $modelClass,
        mixed $key,
        string $indexRoute,
        string $editRoute,
        string $missingMessage = 'That item is no longer available or does not exist.',
        bool $withTrashed = false
    ): RedirectResponse {
        $keyStr = is_int($key) ? (string) $key : ltrim((string) $key, '+');
        if ($keyStr === '' || ! ctype_digit($keyStr)) {
            return redirect()->route($indexRoute)->with('error', $missingMessage);
        }

        $id = (int) $keyStr;

        $query = $modelClass::query();
        if ($withTrashed && method_exists($modelClass, 'withTrashed')) {
            $query->withTrashed();
        }

        if (! $query->whereKey($id)->exists()) {
            return redirect()->route($indexRoute)->with('error', $missingMessage);
        }

        return redirect()->route($editRoute, $id);
    }
}
