FROM php:8.3-fpm

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

COPY ./package.json /var/www/html/package.json

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    unzip \
    git \
    curl \
    libicu-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_pgsql intl opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony \
    && echo "short_open_tag = Off" >> /usr/local/etc/php/conf.d/99-custom.ini \
    && npm install \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer install --optimize-autoloader --no-dev

RUN mkdir -p /var/www/html/var/cache /var/www/html/var/logs /var/www/html/config/jwt /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/var/cache /var/www/html/var/logs /var/www/html/config/jwt /var/www/html/public/uploads

EXPOSE 9000

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
