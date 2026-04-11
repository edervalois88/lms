#!/bin/sh

echo "🚀 Iniciando Protocolo de Despegue NexusEdu (v11.1 Alpine-Compatible)..."

# 1. Inyectando variables de entorno en el .env físico
echo "📝 Inyectando variables de entorno..."
env | grep -E '^(DB_|APP_|REDIS_|MAIL_|QUEUE_|VITE_|ANTHROPIC_|SESSION_|CACHE_)' > .env

# Forzamos drivers que no requieran Redis ni permisos de archivo complejos
echo "LOG_CHANNEL=errorlog" >> .env
echo "CACHE_STORE=database" >> .env
echo "SESSION_DRIVER=database" >> .env
echo "QUEUE_CONNECTION=database" >> .env

# 2. Creación EXPLÍCITA de carpetas (Sin usar llaves {} para compatibilidad con Alpine sh)
echo "📂 Asegurando directorios de sistema..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# 3. Permisos Totales
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

echo "📂 Preparando Estructura de Datos..."
php artisan config:clear
php artisan migrate --force --seed || echo "⚠️ Advertencia en migraciones."

echo "📂 Limpieza Final..."
php artisan optimize:clear
php artisan storage:link || true

# Re-aplicar permisos tras los comandos de artisan
chmod -R 777 storage bootstrap/cache

echo "✅ NexusEdu está LIVE."
exec /usr/bin/supervisord -c /etc/supervisord.conf
