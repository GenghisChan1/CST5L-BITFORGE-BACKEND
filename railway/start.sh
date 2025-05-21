#!/bin/bash

# Wait for database
echo "Waiting for database..."
timeout=60
while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent && [ $timeout -gt 0 ]; do
    sleep 5
    timeout=$((timeout-5))
    echo "Database not ready... ($timeout seconds remaining)"
done

# Verify connection
if ! mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE"; then
    echo "ERROR: Database connection failed!"
    exit 1
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Start services
echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
nginx -g 'daemon off;'