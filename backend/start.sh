#!/bin/bash
set -e

# Otorgar permisos al usuario de Apache sobre las carpetas críticas de Laravel
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Limpiar y cachear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones
php artisan migrate --force

# Iniciar Apache
exec apache2-foreground