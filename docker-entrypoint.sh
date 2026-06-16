#!/bin/sh

# Esperar un par de segundos a que todo esté listo e iniciar migraciones
echo "Corriendo migraciones..."
php artisan migrate --force

# Iniciar el servidor Apache en primer plano (comando nativo de la imagen oficial)
echo "Iniciando Apache..."
exec apache2-foreground