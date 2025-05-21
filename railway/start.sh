#!/bin/bash

# Wait for database (with timeout)
timeout=60
while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent && [ $timeout -gt 0 ]; do
    sleep 5
    timeout=$((timeout-5))
    echo "Waiting for database... ($timeout seconds remaining)"
done

# Verify database connection
if ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; then
    echo "ERROR: Could not connect to database!"
    exit 1
fi

# Run migrations with retries
max_retries=3
attempt=1
until php artisan migrate --force; do
    if [ $attempt -ge $max_retries ]; then
        echo "Migration failed after $max_retries attempts"
        exit 1
    fi
    echo "Migration attempt $attempt failed, retrying..."
    attempt=$((attempt+1))
    sleep 5
done

# Generate nginx config
envsubst '\$PORT' < /var/www/html/nginx.conf > /etc/nginx/conf.d/default.conf

# Start services
php-fpm -D
nginx -g 'daemon off;'