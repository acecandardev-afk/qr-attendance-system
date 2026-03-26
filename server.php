<?php

/**
 * Router for PHP's built-in server (`php artisan serve`).
 *
 * Laravel's ServeCommand uses this file from the project root when present,
 * because some framework releases omit `vendor/.../resources/server.php`.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
