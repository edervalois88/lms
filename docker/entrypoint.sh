#!/bin/sh

echo "🚀 Iniciando Protocolo de Despegue NexusEdu (v8.4 Environment Force)..."

# 1. Forzar la creación del .env con las variables de Railway
# Esto asegura que Laravel use MYSQL y no SQLite
echo "📝 Inyectando variables de entorno..."
env | grep -E '^(DB_|APP_|REDIS_|MAIL_|QUEUE_|VITE_|ANTHROPIC_)' > .env
echo "APP_DEBUG=true" >> .env
echo "APP_ENV=production" >> .env

# Función para esperar a la base de datos
wait_for_db() {
  echo "⏳ Esperando a que MySQL responda en $DB_HOST:$DB_PORT..."
  while ! nc -z $DB_HOST $DB_PORT; do
    sleep 2
  done
  echo "✅ ¡Base de Datos detectada!"
}

if [ -n "$DB_HOST" ]; then
  wait_for_db
fi

echo "📂 Preparando Estructura de Datos..."
# Limpieza de caches antes de migrar
php artisan config:clear
php artisan migrate --force --seed || echo "⚠️ Advertencia en migraciones."

echo "📂 Limpieza Final..."
php artisan optimize:clear
php artisan storage:link || true

echo "✅ NexusEdu está LIVE."
exec /usr/bin/supervisord -c /etc/supervisord.conf
