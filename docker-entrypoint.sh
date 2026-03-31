#!/bin/bash

# Create storage symlink (ignore if already exists)
php artisan storage:link --quiet 2>/dev/null || true

# Cache config, routes, and views (best-effort — don't crash if DB is unavailable)
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Hand off to the CMD (apache2-foreground)
exec "$@"
