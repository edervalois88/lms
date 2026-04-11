#!/bin/sh

echo "🚀 Iniciando Protocolo de Despegue NexusEdu (MySQL Edition)..."

# Esperar a que MySQL esté listo
sleep 7

echo "📂 Preparando Base de Datos..."
# Forzamos la migración limpia para asegurar que todas las tablas (incluida users) se creen en el orden correcto
php artisan migrate:fresh --force --seed || echo "⚠️ Advertencia: Error en migración. Verifica que DB_CONNECTION sea 'mysql' y que las credenciales sean correctas."

echo "📂 Limpiando Caché..."
php artisan optimize:clear

echo "✅ Sistema Online."
exec /usr/bin/supervisord -c /etc/supervisord.conf
