#!/bin/sh

echo "🚀 Iniciando Protocolo de Despegue NexusEdu..."

# Esperar un poco a que la DB esté lista
sleep 5

echo "📂 Ejecutando Migraciones..."
php artisan migrate --force

echo "🌱 Poblando base de datos con carreras UNAM (si es necesario)..."
# Solo ejecutamos el seeder si la tabla de carreras está vacía o el usuario lo ha pedido
# Para este caso inicial, lo ejecutaremos para asegurar que vea las 50 carreras
php artisan db:seed --force

echo "✅ Sistema Listo. Iniciando Servidor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
