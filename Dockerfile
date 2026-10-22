FROM php:8.4-apache-bookworm

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=10000 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates unzip libpq-dev libonig-dev libicu-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pdo_mysql mbstring intl zip bcmath pcntl opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN printf 'Listen ${PORT}\n' > /etc/apache2/ports.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/quickpick.ini

COPY . .

RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && sed -i 's/\r$//' docker/start.sh \
    && chmod +x docker/start.sh

EXPOSE 10000

ENTRYPOINT ["/var/www/html/docker/start.sh"]
CMD ["apache2-foreground"]
