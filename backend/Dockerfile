FROM php:8.4-apache

# 1. Habilitar mod_rewrite de Apache para las rutas de Laravel
RUN a2enmod rewrite

# 2. Cambiar la raíz de Apache a la carpeta /public de Laravel (ESTO ARREGLA EL ERROR 404)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 3. Instalar dependencias del sistema y extensiones de PHP
RUN apt-get update && apt-get install -y \
    libpq-dev zip unzip git \
    && docker-php-ext-install pdo pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/*

# 4. Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5. Establecer directorio de trabajo
WORKDIR /var/www/html

# 6. Copiar dependencias e instalarlas antes de copiar todo el código (Mejora la caché de Docker)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# 7. Copiar el resto del código del proyecto
COPY . .

# 8. Crear carpetas de Laravel si no existen y darles dueño
RUN mkdir -p storage/framework/cache storage/framework/sessions \
    storage/framework/views bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 9. Configurar Apache para que escuche en el puerto 10000 (Requisito de Render)
RUN sed -i 's/Listen 80/Listen 10000/g' /etc/apache2/ports.conf

# 10. Copiar start.sh a la ruta correcta y darle permisos (ESTO ARREGLA EL ERROR DE TU TERMINAL)
COPY start.sh /var/www/html/start.sh
RUN chmod +x /var/www/html/start.sh

# 11. Exponer el puerto
EXPOSE 10000

# 12. Comando final que Render ejecutará al iniciar
CMD ["/var/www/html/start.sh"]