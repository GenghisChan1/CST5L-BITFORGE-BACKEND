# Stage 1: PHP-FPM Setup
FROM php:8.2-fpm AS php

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    default-mysql-client \ 
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo_mysql opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy ALL files first (including artisan)
COPY . .

# Install PHP dependencies (no dev packages)
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan package:discover --ansi

# Set permissions
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# ----------------------------------------
# Stage 2: Nginx Setup
FROM nginx:alpine

# Remove default Nginx config
RUN rm /etc/nginx/conf.d/default.conf

# Copy custom Nginx config
COPY nginx.conf /etc/nginx/conf.d

# Copy PHP-FPM config
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copy application from PHP stage
COPY --from=php /var/www/html /var/www/html

# Copy startup script
COPY railway/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE ${PORT}
CMD ["/start.sh"]