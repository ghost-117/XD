#!/bin/bash
set -e

# Asegurar que las carpetas existen
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}
mkdir -p /var/www/html/bootstrap/cache

# Cambiar el dueño y permisos de manera recursiva
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Limpiar cache de configuración
php /var/www/html/artisan optimize:clear

# Ejecutar migraciones
php /var/www/html/artisan migrate --force

# Iniciar Apache
exec apache2-foreground