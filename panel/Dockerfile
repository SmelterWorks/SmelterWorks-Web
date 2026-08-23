# syntax=docker/dockerfile:1

ARG PHP_IMAGE=php:8.5-fpm-alpine@sha256:41848df84031c4fe6e898f79461ec62edc4c7008e9a5e361e552859b0482b9d7
ARG COMPOSER_IMAGE=composer:2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040

FROM ${COMPOSER_IMAGE} AS vendor
WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_DEV=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-interaction

FROM ${PHP_IMAGE} AS runtime
RUN apk add --no-cache nginx supervisor sqlite-libs icu-libs libzip \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql pdo_pgsql intl zip opcache bcmath sodium

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
    && mkdir -p storage bootstrap/cache database \
    && chown -R www-data:www-data storage bootstrap/cache database

ENV APP_ENV=production \
    APP_DEBUG=false \
    PANEL_MODE=managed \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/database.sqlite

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
