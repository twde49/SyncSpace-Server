#!/bin/bash
set -e

# Create JWT directory if it doesn't exist
mkdir -p /var/jwt
mkdir -p /var/www/html/config/jwt

# Check if the JWT keys exist in the volume
if [ ! -f /var/jwt/private.pem ] || [ ! -f /var/jwt/public.pem ]; then
    echo "JWT keys not found in volume. Generating new keys..."
    # Generate the JWT keys
    php bin/console lexik:jwt:generate-keypair --skip-if-exists

    # Copy the keys to the persistent volume
    if [ -f /var/www/html/config/jwt/private.pem ] && [ -f /var/www/html/config/jwt/public.pem ]; then
        cp /var/www/html/config/jwt/private.pem /var/jwt/
        cp /var/www/html/config/jwt/public.pem /var/jwt/
        echo "Keys generated and copied to persistent volume."
    else
        echo "Failed to generate JWT keys!"
        exit 1
    fi
else
    echo "JWT keys found in volume. Using existing keys..."
    # Copy the keys from the volume to the application
    cp /var/jwt/private.pem /var/www/html/config/jwt/
    cp /var/jwt/public.pem /var/www/html/config/jwt/
    echo "Keys copied from persistent volume."
fi

# Set proper permissions
chmod 644 /var/www/html/config/jwt/*.pem

# Execute the main container command
exec "$@"
