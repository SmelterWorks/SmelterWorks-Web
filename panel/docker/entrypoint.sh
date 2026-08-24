#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

mkdir -p database/migrations storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app bootstrap/cache
touch storage/app/panel.sqlite
chown -R www-data:www-data database storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache database 2>/dev/null || true

if [ -d /opt/panel-migrations ]; then
  cp -rn /opt/panel-migrations/. database/migrations/
fi

php artisan migrate --force
php artisan package:discover --ansi

exec /usr/bin/supervisord -c /etc/supervisord.conf
