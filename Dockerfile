FROM php:8.3-fpm

COPY ./package.json /var/www/html/package.json

RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    libicu-dev \
    && docker-php-ext-install pdo pdo_pgsql intl \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony \
    && docker-php-ext-install opcache \
    && echo "short_open_tag = Off" >> /usr/local/etc/php/conf.d/99-custom.ini \
    && apt-get install -y nodejs npm \
    && npm install

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


COPY . .

RUN composer install --optimize-autoloader


RUN mkdir -p /var/www/html/var/cache /var/www/html/var/logs /var/www/html/config/jwt /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/var/cache /var/www/html/var/logs /var/www/html/config/jwt /var/www/html/public/uploads

EXPOSE 9000

# We'll generate JWT keys in an entrypoint script instead
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
