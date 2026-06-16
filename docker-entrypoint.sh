#!/bin/sh

# Configurar Apache para escuchar en el puerto que Render asigna dinámicamente
if [ -n "$PORT" ]; then
    sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
fi

# (Removimos la migración automática aquí porque en Render a veces hace que el servidor tarde en arrancar y Render lo apaga por seguridad. La migración se hará manual)

# Iniciar el servidor Apache en primer plano (comando nativo de la imagen oficial)
echo "Iniciando Apache..."
exec apache2-foreground