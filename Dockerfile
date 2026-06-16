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

# Dar permisos a la carpeta web
RUN chown -R www-data:www-data /var/www/html

# Ejecutar el comando que fallaba anteriormente
RUN composer install --no-interaction --optimize-autoloader

# Exponer el puerto por defecto de Apache
EXPOSE 80