#!/bin/bash

# Generate nginx config with dynamic PORT
envsubst '\$PORT' < /app/nginx.conf > /etc/nginx/nginx.conf

# Start PHP-FPM in background
php-fpm -y /etc/php/php-fpm.conf -D

# Run Laravel optimizations
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (if needed)
php artisan migrate --force

# Start Nginx in foreground
nginx -c /etc/nginx/nginx.conf -g 'daemon off;'