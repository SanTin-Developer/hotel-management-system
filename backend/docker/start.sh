#!/bin/bash
set -e

LISTEN_PORT="${PORT:-10000}"
sed -i "s/__PORT__/${LISTEN_PORT}/" /etc/nginx/sites-available/default

# Cache config for performance (safe since env vars are set in Render dashboard)
php artisan config:cache

# Run migrations on every deploy (safe to run repeatedly — only applies new ones)
php artisan migrate --force

# Start nginx + php-fpm together
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
