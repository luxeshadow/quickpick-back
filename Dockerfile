FROM php:8.4-apache-bookworm

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=10000 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates curl unzip libpq-dev libonig-dev libicu-dev libzip-dev \
        python3 python3-venv supervisor \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pdo_mysql mbstring intl zip bcmath pcntl opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN printf 'Listen ${PORT}\n' > /etc/apache2/ports.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/quickpick.ini
COPY docker/supervisord.conf /etc/supervisor/quickpick.conf
COPY docker/apache-workers.conf /etc/apache2/mods-available/mpm_prefork.conf

COPY face-service/pyproject.toml face-service/README.md /opt/face-service/
COPY face-service/app /opt/face-service/app
RUN python3 -m venv /opt/face-venv \
    && /opt/face-venv/bin/pip install --no-cache-dir /opt/face-service

ARG YUNET_SHA256=8f2383e4dd3cfbb4553ea8718107fc0423210dc964f9f4280604804ed2552fa4
ARG SFACE_SHA256=0ba9fbfa01b5270c96627c4ef784da859931e02f04419c829e83484087c34e79
RUN mkdir -p /opt/models \
    && curl -fsSL https://github.com/opencv/opencv_zoo/raw/main/models/face_detection_yunet/face_detection_yunet_2023mar.onnx -o /opt/models/yunet.onnx \
    && echo "$YUNET_SHA256  /opt/models/yunet.onnx" | sha256sum -c - \
    && curl -fsSL https://huggingface.co/opencv/face_recognition_sface/resolve/main/face_recognition_sface_2021dec.onnx -o /opt/models/sface.onnx \
    && echo "$SFACE_SHA256  /opt/models/sface.onnx" | sha256sum -c -

ENV YUNET_MODEL_PATH=/opt/models/yunet.onnx \
    YUNET_MODEL_SHA256=${YUNET_SHA256} \
    SFACE_MODEL_PATH=/opt/models/sface.onnx \
    SFACE_MODEL_SHA256=${SFACE_SHA256} \
    MODEL_VERSION=sface-2021dec-v1 \
    OMP_NUM_THREADS=1 \
    OPENBLAS_NUM_THREADS=1

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
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/quickpick.conf"]
