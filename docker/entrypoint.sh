#!/bin/sh
set -e

# Wait for MySQL to be ready (max 30 seconds)
echo "Waiting for MySQL..."
max_tries=30
count=0
while [ $count -lt $max_tries ]; do
    if nc -z deals-mysql 3306; then
        echo "MySQL is up!"
        break
    fi
    echo "MySQL is still down, waiting..."
    sleep 1
    count=$((count + 1))
done

if [ $count -eq $max_tries ]; then
    echo "Error: MySQL did not become ready in time"
    # Proceed anyway to see app errors if MySQL is permanently down
fi

# Check if migrations table exists
if ! php artisan migrate:status > /dev/null 2>&1; then
    echo "Database not initialized. Running migrations and seeds..."
    php artisan migrate --force
    php artisan db:seed --force
else
    echo "Database already initialized."
fi

exec php-fpm
