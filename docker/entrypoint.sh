#!/bin/sh
set -eu

echo "🚀 Iniciando Protocolo de Despegue NexusEdu (v11.1 Alpine-Compatible)..."

# 1. Inyectando variables de entorno en el .env físico
echo "📝 Inyectando variables de entorno..."
env | grep -E '^(DB_|APP_|REDIS_|MAIL_|QUEUE_|VITE_|ANTHROPIC_|GROQ_|VECTOR_|SESSION_|CACHE_|LOG_|ENABLE_QUEUE_WORKER|QUEUE_WORKER_)' > .env || true

# Defaults seguros (respetan variables explícitas definidas en Railway)
SESSION_DRIVER_VALUE="${SESSION_DRIVER:-database}"
CACHE_STORE_VALUE="${CACHE_STORE:-database}"
QUEUE_CONNECTION_VALUE="${QUEUE_CONNECTION:-database}"

# Garantiza que Laravel tenga estos valores en el .env generado
echo "LOG_CHANNEL=errorlog" >> .env
echo "CACHE_STORE=${CACHE_STORE_VALUE}" >> .env
echo "SESSION_DRIVER=${SESSION_DRIVER_VALUE}" >> .env
echo "QUEUE_CONNECTION=${QUEUE_CONNECTION_VALUE}" >> .env

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

AUTO_RUN_MIGRATIONS="${AUTO_RUN_MIGRATIONS:-true}"
AUTO_RUN_SEEDERS="${AUTO_RUN_SEEDERS:-false}"

if [ "$AUTO_RUN_MIGRATIONS" = "true" ]; then
  if [ "$AUTO_RUN_SEEDERS" = "true" ]; then
    echo "🗄️ Ejecutando migraciones + seeders..."
    php artisan migrate --force --seed
  else
    echo "🗄️ Ejecutando migraciones pendientes..."
    php artisan migrate --force
  fi
else
  echo "⏭️ AUTO_RUN_MIGRATIONS=false, se omite migrate."
fi

echo "📂 Limpieza Final..."
AUTO_OPTIMIZE_CLEAR="${AUTO_OPTIMIZE_CLEAR:-true}"
AUTO_CONFIG_CACHE="${AUTO_CONFIG_CACHE:-true}"

if [ "$AUTO_OPTIMIZE_CLEAR" = "true" ]; then
  php artisan optimize:clear
fi

if [ "$AUTO_CONFIG_CACHE" = "true" ]; then
  php artisan config:cache
fi

php artisan storage:link || true

# Worker auto-gestionado (opcional)
mkdir -p /etc/supervisor/conf.d
ENABLE_QUEUE_WORKER_VALUE="${ENABLE_QUEUE_WORKER:-false}"
QUEUE_WORKER_SLEEP_VALUE="${QUEUE_WORKER_SLEEP:-1}"
QUEUE_WORKER_TRIES_VALUE="${QUEUE_WORKER_TRIES:-3}"
QUEUE_WORKER_TIMEOUT_VALUE="${QUEUE_WORKER_TIMEOUT:-120}"

if [ "$ENABLE_QUEUE_WORKER_VALUE" = "true" ]; then
  echo "⚙️ Habilitando Queue Worker auto-gestionado en supervisord..."
  cat > /etc/supervisor/conf.d/queue-worker.conf <<EOF
[program:queue-worker]
command=php /var/www/html/artisan queue:work --sleep=${QUEUE_WORKER_SLEEP_VALUE} --tries=${QUEUE_WORKER_TRIES_VALUE} --timeout=${QUEUE_WORKER_TIMEOUT_VALUE}
directory=/var/www/html
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF
else
  rm -f /etc/supervisor/conf.d/queue-worker.conf
  echo "⏭️ ENABLE_QUEUE_WORKER=false, worker no iniciado en este contenedor."
fi

# Re-aplicar permisos tras los comandos de artisan
chmod -R 777 storage bootstrap/cache

echo "✅ NexusEdu está LIVE."
exec /usr/bin/supervisord -c /etc/supervisord.conf
