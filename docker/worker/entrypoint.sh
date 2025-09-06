#!/bin/bash
set -e

# Create supervisor log directory if it doesn't exist
mkdir -p /var/log/supervisor

# Wait for database to be ready
echo "Waiting for database connection..."
maxTries=10
while [ $maxTries -gt 0 ]; do
    if php bin/console doctrine:query:sql "SELECT 1" &> /dev/null; then
        break
    fi
    maxTries=$((maxTries-1))
    echo "Waiting for database to be ready... $maxTries"
    sleep 3
done

if [ $maxTries -eq 0 ]; then
    echo "Could not connect to database"
    exit 1
fi

# Run migrations if needed
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Clear cache
php bin/console cache:clear

# Ensure correct permissions
chown -R www-data:www-data /var/www/html/var

# Print debug info if WORKER_DEBUG is set
if [ "$WORKER_DEBUG" = "1" ]; then
    echo "Debug mode enabled"
    php bin/console debug:container messenger.receiver_locator
    php bin/console debug:messenger
fi

echo "Starting worker service..."
exec "$@"
