#!/bin/sh

echo "🚀 Iniciando Protocolo de Despegue NexusEdu (v11 Cloud-Native)..."

# 1. Forzar una configuración limpia que no dependa de archivos físicos para logs
echo "📝 Inyectando configuración de nube..."
env | grep -E '^(DB_|APP_|REDIS_|MAIL_|QUEUE_|VITE_|ANTHROPIC_|SESSION_|CACHE_)' > .env

# Forzamos drivers que no requieran Redis ni permisos de archivo complejos
echo "LOG_CHANNEL=errorlog" >> .env
echo "CACHE_STORE=database" >> .env
echo "SESSION_DRIVER=database" >> .env
echo "QUEUE_CONNECTION=database" >> .env

# 2. Permisos totales (Cinturón y Tirantes)
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Función para esperar a la base de datos
wait_for_db() {
  echo "⏳ Esperando a que MySQL responda..."
  while ! nc -z $DB_HOST $DB_PORT; do
    sleep 2
  done
  echo "✅ ¡Base de Datos detectada!"
}

if [ -n "$DB_HOST" ]; then
  wait_for_db
fi

echo "📂 Preparando Base de Datos..."
php artisan config:clear
# Usamos database para cache/session, por lo que necesitamos estas tablas
php artisan session:table || true
php artisan cache:table || true
php artisan migrate --force --seed || echo "⚠️ Advertencia en migraciones."

echo "📂 Limpieza Final..."
php artisan optimize:clear
php artisan storage:link || true

# Re-aplicar permisos justo antes de arrancar
chmod -R 777 storage bootstrap/cache

echo "✅ NexusEdu está LIVE."
exec /usr/bin/supervisord -c /etc/supervisord.conf
