# syntax=docker/dockerfile:1

ARG PHP_IMAGE=php:8.5-fpm-alpine@sha256:41848df84031c4fe6e898f79461ec62edc4c7008e9a5e361e552859b0482b9d7
ARG COMPOSER_IMAGE=composer:2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040
ARG NODE_IMAGE=node:24-alpine@sha256:d32cdf619f63fe0471182d08996dd516c6275bb5fd31ae06e55a570bd9e1ad43

FROM ${NODE_IMAGE} AS assets
WORKDIR /app
COPY package.json ./
RUN corepack enable pnpm \
    && corepack prepare pnpm@11.20.0 --activate \
    && pnpm install
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
ENV NODE_ENV=production
RUN pnpm run build && rm -rf node_modules

FROM ${COMPOSER_IMAGE} AS vendor
WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_DEV=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-interaction --no-scripts

FROM ${PHP_IMAGE} AS runtime
RUN apk add --no-cache nginx supervisor sqlite-libs icu-libs libzip wget \
    sqlite-dev icu-dev libzip-dev \
    && docker-php-ext-install pdo_sqlite intl zip \
    && apk del sqlite-dev icu-dev libzip-dev

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs database bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache database

ENV APP_ENV=production \
    APP_DEBUG=false \
    PANEL_MODE=managed \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/database.sqlite

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
