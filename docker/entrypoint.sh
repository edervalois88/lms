#!/bin/sh

echo "🚀 Iniciando Protocolo de Despegue NexusEdu (v8.4 Resilient)..."

# Esperar un poco a que la DB esté lista
sleep 5

echo "📂 Intentando Migraciones Automáticas..."
# Usamos || true para que si falla la DB, el servidor Nginx igual encienda y nos deje ver el error real
php artisan migrate --force || echo "⚠️ Advertencia: Las migraciones fallaron. Verifica la conexión a la DB."

echo "🌱 Intentando Cargar Datos (Seeders)..."
php artisan db:seed --force || echo "⚠️ Advertencia: El poblado de datos falló o ya estaba realizado."

echo "📂 Limpiando Caché de Aplicación..."
php artisan optimize:clear

echo "✅ Protocolo finalizado. Iniciando Capas de Servicio..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
