#!/bin/bash
set -e

# Usar el puerto que Render asigna via $PORT, o 80 por defecto
APACHE_PORT=${PORT:-80}
sed -i "s/Listen 80/Listen $APACHE_PORT/g" /etc/apache2/ports.conf
sed -i "s/*:80/*:$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

exec apache2-foreground