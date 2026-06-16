#!/bin/sh

# Configurar Apache para escuchar en el puerto que Render asigna dinámicamente
if [ -n "$PORT" ]; then
    sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
fi

# Esperar un par de segundos a que todo esté listo e iniciar migraciones
echo "Corriendo migraciones..."
php artisan migrate --force

# Iniciar el servidor Apache en primer plano (comando nativo de la imagen oficial)
echo "Iniciando Apache..."
exec apache2-foreground