#!/bin/bash

# Create storage symlink
php artisan storage:link --quiet 2>/dev/null || true

# Cache config, routes, and views
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Start Laravel's built-in server on the PORT Railway provides (default 8000)
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
