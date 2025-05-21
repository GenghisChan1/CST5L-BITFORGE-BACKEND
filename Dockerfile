# Single stage build (combines PHP-FPM and Nginx)
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
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

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan package:discover --ansi

# Configure Nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf
RUN rm /etc/nginx/sites-enabled/default

# Configure PHP-FPM
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Set permissions
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Copy startup script
COPY railway/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE ${PORT}
CMD ["/start.sh"]