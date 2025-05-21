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

# Generate nginx config with dynamic PORT
envsubst '\$PORT' < /app/nginx.conf > /etc/nginx/nginx.conf

# Start PHP-FPM in background
php-fpm -y /etc/php/php-fpm.conf -D

# Run Laravel optimizations
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run Laravel setup
php artisan migrate --force
php artisan optimize:clear

# Create necessary directories
mkdir -p /var/run/php
chown -R nobody:nobody /var/run/php

# Start PHP-FPM with explicit config
php-fpm &
nginx -g 'daemon off;'