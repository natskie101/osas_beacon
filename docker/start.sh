#!/bin/sh
set -e

# Render injects PORT (default 10000); Apache's stock config listens on 80.
PORT="${PORT:-80}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\*:80/*:${PORT}/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

# Fail fast if the database is unreachable: `artisan migrate --force` used to
# exit 0 on a broken DSN (setAutoExit(false) + no exit($status) in artisan),
# so a bad DB_HOST produced a "successful" deploy that 500'd every page.
# (SQLSTATE 08006: a stray " <- no -pooler" annotation in DB_HOST.)
php artisan db:monitor --databases=pgsql >/dev/null 2>&1 \
    || { echo "FATAL: cannot reach the database (check DB_HOST/DB_* env vars)" >&2; exit 1; }

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground