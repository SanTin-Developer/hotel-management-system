#!/bin/bash

set -e

LISTEN_PORT="${PORT:-10000}"

sed -i "s/__PORT__/${LISTEN_PORT}/" /etc/nginx/sites-available/default

# Run migrations
php artisan migrate --force

# Seed initial/sample production data
php artisan db:seed --class=SampleDataSeeder --force

# Cache configuration after all environment variables are available
php artisan config:cache

# Start nginx + php-fpm together
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
