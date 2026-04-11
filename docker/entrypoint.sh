#!/bin/sh

echo "🚀 Iniciando Protocolo de Despegue NexusEdu (v8.4 Permission & Redis Fix)..."

# 1. Inyectando variables de entorno en el .env físico
echo "📝 Inyectando variables de entorno..."
env | grep -E '^(DB_|APP_|REDIS_|MAIL_|QUEUE_|VITE_|ANTHROPIC_|SESSION_|CACHE_)' > .env

# 2. Forzar drivers seguros si no hay Redis configurado en Railway
# Esto evita el error "Class Redis not found" si el servicio no existe
echo "CACHE_STORE=file" >> .env
echo "SESSION_DRIVER=file" >> .env
echo "QUEUE_CONNECTION=sync" >> .env

# 3. Permisos AGRESIVOS para evitar el Error 500 de logs
echo "🔓 Ajustando permisos de almacenamiento..."
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

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
# Limpieza de caches antes de intentar nada
php artisan config:clear
php artisan migrate --force --seed || echo "⚠️ Advertencia en migraciones."

echo "📂 Optimización Final..."
php artisan optimize:clear
php artisan storage:link || true

# Re-aplicar permisos por si algún comando de artisan creó archivos como root
chmod -R 777 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "✅ NexusEdu está LIVE."
exec /usr/bin/supervisord -c /etc/supervisord.conf
