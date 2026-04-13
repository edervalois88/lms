#!/bin/bash

# Script para preparar la aplicación después de deploy en Railway
echo "🚀 Iniciando configuración post-deploy..."

# 1. Generar .env si no existe
if [ ! -f .env ]; then
    echo "📝 Generando archivo .env..."
    cp .env.example .env || echo "⚠️ No se encontró .env.example"
fi

# 2. Ejecutar migraciones
echo "🗄️ Ejecutando migraciones..."
php artisan migrate --force

# 3. Ejecutar seeders
echo "🌱 Ejecutando seeders..."
php artisan db:seed --force

# 4. Configurar sistema de recompensas
echo "🎁 Configurando sistema de recompensas..."
php artisan rewards:setup

# 5. Limpiar cache
echo "🧹 Limpiando cache..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo "✅ ¡Configuración completada!"
