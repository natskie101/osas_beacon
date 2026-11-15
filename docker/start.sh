#!/bin/sh
set -e

PORT="${PORT:-10000}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\*:80/*:${PORT}/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

php artisan db:monitor --databases=pgsql >/dev/null 2>&1 \
    || { echo "FATAL: cannot reach the database (check DB_HOST/DB_* env vars)" >&2; exit 1; }

php artisan migrate --force
php artisan view:clear
php artisan view:cache

exec apache2-foreground
