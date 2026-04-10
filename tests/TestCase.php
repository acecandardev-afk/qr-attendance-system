<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cached config (from `php artisan config:cache`) ignores phpunit.xml DB_* env vars and breaks tests.
     */
    protected function setUp(): void
    {
        $path = dirname(__DIR__).'/bootstrap/cache/config.php';
        if (is_file($path)) {
            @unlink($path);
        }

        parent::setUp();
    }
}
