#!/bin/bash

# Wait for database to be ready (only if using internal DB)
while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
    sleep 1
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
php-fpm 

# Start Nginx
nginx -g 'daemon off;'