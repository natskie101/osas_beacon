#!/bin/sh
set -e

# Render injects PORT (default 10000); Apache's stock config listens on 80.
PORT="${PORT:-80}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\*:80/*:${PORT}/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground