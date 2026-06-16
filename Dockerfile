FROM php:8.2-apache

# Instalar extensiones de PHP necesarias y Composer
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql

# Copiar Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar los archivos de tu proyecto al servidor
COPY . /var/www/html/

# Configurar Apache para Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# Dar permisos a la carpeta web
RUN chown -R www-data:www-data /var/www/html

# Ejecutar el comando que fallaba anteriormente
RUN composer install --no-interaction --optimize-autoloader

# Exponer el puerto por defecto de Apache
EXPOSE 80

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
CMD ["docker-entrypoint.sh"]