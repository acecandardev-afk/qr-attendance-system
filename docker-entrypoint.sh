#!/bin/bash
set -e

# Create storage symlink (public/storage -> storage/app/public)
php artisan storage:link --quiet 2>/dev/null || true

# Cache config, routes, and views for better performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Hand off to the CMD (apache2-foreground)
exec "$@"
