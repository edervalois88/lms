#!/bin/sh

echo "🚀 Iniciando Protocolo de Despegue NexusEdu (v8.4 Ultra-Resilient)..."

# Función para esperar a la base de datos
wait_for_db() {
  echo "⏳ Esperando a que MySQL responda en $DB_HOST:$DB_PORT..."
  while ! nc -z $DB_HOST $DB_PORT; do
    sleep 2
  done
  echo "✅ ¡Base de Datos detectada!"
}

# Solo esperamos si las variables están seteadas
if [ -n "$DB_HOST" ]; then
  wait_for_db
fi

echo "📂 Preparando Estructura de Datos..."
# Forzamos la migración limpia
php artisan migrate:fresh --force --seed || echo "⚠️ Fallo en migración. Probando con migración simple..."
php artisan migrate --force

echo "📂 Optimizando Motores..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ NexusEdu está LIVE."
exec /usr/bin/supervisord -c /etc/supervisord.conf
