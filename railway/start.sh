#!/bin/bash

# Debug: Show environment variables
echo "----- DB CONFIG DEBUG -----"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"
echo "---------------------------"

# Wait for database with more verbose output
echo "Waiting for database..."
timeout=60
while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent && [ $timeout -gt 0 ]; do
    sleep 5
    timeout=$((timeout-5))
    echo "Database not ready... ($timeout seconds remaining)"
    
    # Additional connection test
    if ! mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SHOW STATUS" 2>&1; then
        echo "Connection attempt failed with above error"
    fi
done

# Verify connection with more detailed output
echo "Verifying database connection..."
if ! mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE; SHOW TABLES"; then
    echo "ERROR: Database connection failed!"
    exit 1
else
    echo "Database connection successful!"
fi

# Run migrations with output
echo "Running migrations..."
php artisan migrate --force -vvv

# Start services
echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
nginx -g 'daemon off;'