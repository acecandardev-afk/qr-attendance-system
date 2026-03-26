<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most applications have two view paths, but this can be customized if
    | needed. These paths are where Blade/HTML templates are loaded from.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | Blade templates are compiled into plain PHP and stored on disk.
    | On Vercel/serverless, project storage is read-only, so we use /tmp.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        env('VERCEL')
            ? sys_get_temp_dir().'/laravel-views'
            : realpath(storage_path('framework/views'))
    ),

];

