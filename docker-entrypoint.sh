#!/bin/bash
set -e

# Create storage symlink
php artisan storage:link --quiet 2>/dev/null || true

# Apply migrations when a database is configured
php artisan migrate --force --no-interaction

# Cache config, routes, and views
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Start Laravel's built-in server (PORT is optional, e.g. in Docker)
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
