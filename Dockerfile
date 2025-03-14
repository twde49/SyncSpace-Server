FROM php:8.3-fpm

COPY ./package.json /var/www/html/package.json

RUN apt update && apt install -y \
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
    && apt install -y nodejs npm \
    && npm install

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


COPY . .

RUN composer install --optimize-autoloader


RUN mkdir -p /var/www/html/var/cache /var/www/html/var/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/var/cache /var/www/html/var/logs

EXPOSE 9000

RUN php bin/console lexik:jwt:generate-keypair --overwrite

CMD ["php-fpm"]
