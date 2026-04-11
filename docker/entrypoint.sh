#!/bin/sh

echo "🚀 Iniciando Protocolo de Despegue NexusEdu (v8.4 Fresh Startup)..."

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
# Ejecutamos migraciones normales (sin fresh para no borrar datos si ya se crearon)
php artisan migrate --force --seed || echo "⚠️ Advertencia en migraciones."

echo "📂 Limpiando TODO el Caché (Modo Seguro)..."
# Desactivamos los caches en producción temporalmente para evitar el Error 500 por config vieja
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "✅ NexusEdu está LIVE."
exec /usr/bin/supervisord -c /etc/supervisord.conf
