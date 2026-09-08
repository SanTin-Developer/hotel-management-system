#!/bin/bash

set -e

LISTEN_PORT="${PORT:-10000}"

echo "========================================"
echo "Starting Hotel Management System"
echo "PORT: ${LISTEN_PORT}"
echo "========================================"

sed -i "s/__PORT__/${LISTEN_PORT}/" /etc/nginx/sites-available/default

echo "========================================"
echo "Running database migrations..."
echo "========================================"

php artisan migrate --force

echo "========================================"
echo "Running SampleDataSeeder..."
echo "========================================"

php artisan db:seed --class=SampleDataSeeder --force

echo "========================================"
echo "SampleDataSeeder completed successfully"
echo "========================================"

echo "========================================"
echo "Caching Laravel configuration..."
echo "========================================"

php artisan config:cache

echo "========================================"
echo "Starting Supervisor..."
echo "========================================"

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
