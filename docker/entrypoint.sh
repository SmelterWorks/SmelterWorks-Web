#!/bin/sh
set -eu

cd /var/www/html

# Named volumes mount as root:root. Start as root, fix ownership, then drop to app.
if [ "$(id -u)" = "0" ]; then
    mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /tmp/nginx/client \
        /tmp/nginx/proxy \
        /tmp/nginx/fastcgi \
        /tmp/nginx/uwsgi \
        /tmp/nginx/scgi \
        /tmp/nginx/logs \
        /var/lib/nginx/logs \
        /var/lib/nginx/tmp
    chown -R app:app \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
        /tmp/nginx \
        /var/lib/nginx
    exec su-exec app:app "$0" "$@"
fi

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /tmp/nginx/client \
    /tmp/nginx/proxy \
    /tmp/nginx/fastcgi \
    /tmp/nginx/uwsgi \
    /tmp/nginx/scgi \
    /tmp/nginx/logs \
    /var/lib/nginx/logs \
    /var/lib/nginx/tmp

if [ ! -f storage/database.sqlite ]; then
    touch storage/database.sqlite
fi

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY must be set for production" >&2
    exit 1
fi

php artisan config:cache --no-ansi
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi

php artisan migrate --force --no-interaction --no-ansi || true

php-fpm -D
exec nginx -g 'daemon off;'
