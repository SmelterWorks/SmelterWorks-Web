# syntax=docker/dockerfile:1

ARG NODE_IMAGE=node:24-alpine@sha256:d32cdf619f63fe0471182d08996dd516c6275bb5fd31ae06e55a570bd9e1ad43
ARG COMPOSER_IMAGE=composer:2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040
ARG PHP_IMAGE=php:8.5-fpm-alpine@sha256:41848df84031c4fe6e898f79461ec62edc4c7008e9a5e361e552859b0482b9d7

ARG OCI_TITLE=SmelterWorks Web
ARG OCI_DESCRIPTION=Public marketing and catalog site for SmelterWorks Vintage Story software, mods, Relic Launcher, and hosting.
ARG OCI_URL=https://github.com/SmelterWorks/SmelterWorks-Web
ARG OCI_SOURCE=https://github.com/SmelterWorks/SmelterWorks-Web
ARG OCI_VENDOR=SmelterWorks
ARG OCI_LICENSES=Apache-2.0
ARG OCI_VERSION=dev
ARG OCI_REVISION=unknown

FROM ${NODE_IMAGE} AS assets

WORKDIR /app

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
COPY packages ./packages
RUN corepack enable \
    && corepack prepare pnpm@11.20.0 --activate \
    && pnpm install --frozen-lockfile \
    && rm -rf /root/.local/share/pnpm/store

COPY vite.config.js ./
COPY scripts ./scripts
COPY resources ./resources
COPY public ./public

ENV NODE_ENV=production
RUN pnpm run build \
    && rm -rf node_modules


FROM ${COMPOSER_IMAGE} AS vendor

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_DEV=1 \
    COMPOSER_ALLOW_PLUGINS=1

COPY composer.json composer.lock ./
COPY packages/vintage-story-serverlist ./packages/vintage-story-serverlist
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    && rm -rf /tmp/composer-cache /root/.composer/cache

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-interaction \
    && rm -rf tests bootstrap/cache/*.php


FROM ${PHP_IMAGE} AS runtime

ARG APP_UID=65532
ARG APP_GID=65532
ARG OCI_TITLE
ARG OCI_DESCRIPTION
ARG OCI_URL
ARG OCI_SOURCE
ARG OCI_VENDOR
ARG OCI_LICENSES
ARG OCI_VERSION
ARG OCI_REVISION
ARG PHP_IMAGE

LABEL org.opencontainers.image.title="${OCI_TITLE}" \
      org.opencontainers.image.description="${OCI_DESCRIPTION}" \
      org.opencontainers.image.url="${OCI_URL}" \
      org.opencontainers.image.source="${OCI_SOURCE}" \
      org.opencontainers.image.vendor="${OCI_VENDOR}" \
      org.opencontainers.image.licenses="${OCI_LICENSES}" \
      org.opencontainers.image.version="${OCI_VERSION}" \
      org.opencontainers.image.revision="${OCI_REVISION}" \
      org.opencontainers.image.base.name="${PHP_IMAGE}" \
      org.opencontainers.image.authors="SmelterWorks"

WORKDIR /var/www/html

RUN apk add --no-cache nginx sqlite-libs curl su-exec \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS sqlite-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_sqlite \
    && apk del --no-network .build-deps \
    && rm -rf /tmp/pear /usr/src/php* /var/cache/apk/* \
    && addgroup -g "${APP_GID}" -S app \
    && adduser -u "${APP_UID}" -S app -G app \
    && mkdir -p \
        /tmp/nginx/client \
        /tmp/nginx/proxy \
        /tmp/nginx/fastcgi \
        /tmp/nginx/uwsgi \
        /tmp/nginx/scgi \
        /var/lib/nginx/logs \
        /var/lib/nginx/tmp \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache \
    && rm -rf /etc/nginx/http.d/default.conf \
    && chown -R app:app /tmp/nginx /var/www/html /var/lib/nginx /var/log/nginx

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY docker/docker.conf /usr/local/etc/php-fpm.d/docker.conf
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/zz-app.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-security.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

COPY --from=vendor --chown=app:app /app/vendor ./vendor
COPY --from=vendor --chown=app:app /app/packages/vintage-story-serverlist ./packages/vintage-story-serverlist

COPY --chown=app:app app ./app
COPY --chown=app:app bootstrap ./bootstrap
COPY --chown=app:app config ./config
COPY --chown=app:app database ./database
COPY --chown=app:app public ./public
COPY --from=assets --chown=app:app /app/public/build ./public/build
COPY --from=assets --chown=app:app /app/public/icons ./public/icons
COPY --chown=app:app resources ./resources
COPY --chown=app:app routes ./routes
COPY --chown=app:app artisan ./artisan
COPY --chown=app:app composer.json ./composer.json
COPY --chown=app:app composer.lock ./composer.lock

RUN chmod 755 /usr/local/bin/entrypoint.sh

# Starts as root so entrypoint can chown named volumes, then drops to app via su-exec.
USER root

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=45s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
