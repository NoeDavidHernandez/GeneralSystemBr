#!/bin/sh

echo "Iniciando Worker de mensajes en segundo plano..."
php artisan queue:work --sleep=3 --tries=3 &

echo "Iniciando Reloj de recordatorios (Cron)..."
(
    while true; do
        php artisan schedule:run
        sleep 60
    done
) &

echo "Iniciando Apache..."
exec apache2-foreground