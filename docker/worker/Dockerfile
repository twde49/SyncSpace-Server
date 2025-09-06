FROM php:8.3-fpm

# Install dependencies
RUN apt update && apt install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    libicu-dev \
    zip \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql intl \
    && docker-php-ext-install opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony \
    && echo "short_open_tag = Off" >> /usr/local/etc/php/conf.d/99-custom.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/99-custom.ini

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# The actual app will be mounted in docker-compose
# This container is specifically for running workers/consumers

# Set permissions
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/var/cache /var/www/html/var/logs

# Create supervisor configuration
RUN mkdir -p /etc/supervisor/conf.d/
COPY docker/worker/supervisor.conf /etc/supervisor/conf.d/worker.conf

# Add entrypoint script
COPY docker/worker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Command to run supervisord which will manage the worker processes
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]
