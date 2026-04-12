#!/bin/sh
set -eu

echo "🚀 Iniciando Protocolo de Despegue NexusEdu (v11.1 Alpine-Compatible)..."

# 1. Defaults seguros por variables de entorno (sin materializar secretos en .env)
echo "📝 Aplicando defaults de entorno en memoria..."
: "${LOG_CHANNEL:=errorlog}"
: "${SESSION_DRIVER:=database}"
: "${CACHE_STORE:=database}"
: "${QUEUE_CONNECTION:=database}"
export LOG_CHANNEL SESSION_DRIVER CACHE_STORE QUEUE_CONNECTION

# 2. Creación EXPLÍCITA de carpetas (Sin usar llaves {} para compatibilidad con Alpine sh)
echo "📂 Asegurando directorios de sistema..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# 3. Permisos seguros
apply_secure_permissions() {
  chown -R www-data:www-data storage bootstrap/cache
  find storage bootstrap/cache -type d -exec chmod 775 {} \;
  find storage bootstrap/cache -type f -exec chmod 664 {} \;
}

apply_secure_permissions

# Función para esperar a la base de datos con reintentos
wait_for_db() {
  DB_HOST_VALUE="${DB_HOST:-}"
  DB_PORT_VALUE="${DB_PORT:-3306}"
  DB_USER_VALUE="${DB_USERNAME:-}"
  DB_PASS_VALUE="${DB_PASSWORD:-}"

  # Railway proporciona DATABASE_URL en lugar de variables separadas.
  # Parsear DATABASE_URL si DB_HOST no está definido.
  if [ -z "$DB_HOST_VALUE" ] && [ -n "${DATABASE_URL:-}" ]; then
    # Formato: mysql://user:password@host:port/dbname
    _url="${DATABASE_URL}"
    # Remover esquema (mysql:// o mysql2://)
    _url="${_url#*://}"
    # Extraer userinfo (antes del @)
    _userinfo="${_url%@*}"
    # Extraer hostinfo (después del @)
    _hostinfo="${_url##*@}"
    # Extraer host y puerto (antes del /)
    _hostport="${_hostinfo%%/*}"
    DB_HOST_VALUE="${_hostport%%:*}"
    _port="${_hostport##*:}"
    # Si el puerto es igual al host (no había :), usar 3306
    if [ "$_port" = "$DB_HOST_VALUE" ]; then
      DB_PORT_VALUE="3306"
    else
      DB_PORT_VALUE="$_port"
    fi
    DB_USER_VALUE="${_userinfo%%:*}"
    DB_PASS_VALUE="${_userinfo#*:}"
    echo "🔍 Parseado DATABASE_URL: host=${DB_HOST_VALUE} port=${DB_PORT_VALUE} user=${DB_USER_VALUE}"
  fi

  if [ -z "$DB_HOST_VALUE" ]; then
    echo "⏭️ DB_HOST y DATABASE_URL no definidos, se omite espera de base de datos."
    return 0
  fi

  echo "⏳ Esperando a que MySQL responda en ${DB_HOST_VALUE}:${DB_PORT_VALUE}..."
  
  MAX_ATTEMPTS=30
  ATTEMPT=1
  
  while [ $ATTEMPT -le $MAX_ATTEMPTS ]; do
    # Intentar conectar directamente con MySQL
    if mysql -h "$DB_HOST_VALUE" -P "$DB_PORT_VALUE" -u "$DB_USER_VALUE" -p"$DB_PASS_VALUE" -e "SELECT 1" >/dev/null 2>&1; then
      echo "✅ MySQL está LISTO en intento $ATTEMPT"
      return 0
    fi
    
    echo "⏳ Intento $ATTEMPT/$MAX_ATTEMPTS: MySQL no está listo aún..."
    sleep 3
    ATTEMPT=$((ATTEMPT + 1))
  done
  
  echo "⚠️ Timeout esperando a MySQL después de $MAX_ATTEMPTS intentos. Continuando de todas formas..."
  return 1
}

if [ "${DB_CONNECTION:-mysql}" != "sqlite" ]; then
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

# Poblacion automatica opcional (runtime) para Railway/produccion.
AUTO_POPULATE_COMIPEMS_RUNTIME_VALUE="${AUTO_POPULATE_COMIPEMS_RUNTIME:-false}"
AUTO_POPULATE_LOTES_VALUE="${AUTO_POPULATE_LOTES:-2}"
AUTO_POPULATE_MATERIAS_VALUE="${AUTO_POPULATE_MATERIAS:-Matemáticas,Física,Química,Biología,Historia Universal,Historia de México,Español,Geografía,Formación Cívica y Ética}"
AUTO_POPULATE_MIN_ACTIVE_QUESTIONS_VALUE="${AUTO_POPULATE_MIN_ACTIVE_QUESTIONS:-1500}"

if [ "$AUTO_POPULATE_COMIPEMS_RUNTIME_VALUE" = "true" ]; then
  CURRENT_QUESTIONS_RAW="$(php artisan tinker --execute="echo App\\Models\\Question::query()->where('is_active', true)->count();" 2>/dev/null || echo 0)"
  CURRENT_QUESTIONS_VALUE="$(echo "$CURRENT_QUESTIONS_RAW" | tr -dc '0-9')"

  if [ -z "$CURRENT_QUESTIONS_VALUE" ]; then
    CURRENT_QUESTIONS_VALUE=0
  fi

  if [ "$CURRENT_QUESTIONS_VALUE" -ge "$AUTO_POPULATE_MIN_ACTIVE_QUESTIONS_VALUE" ]; then
    echo "⏭️ Ya hay suficientes reactivos activos ($CURRENT_QUESTIONS_VALUE >= $AUTO_POPULATE_MIN_ACTIVE_QUESTIONS_VALUE). Se omite poblacion automatica."
  else
    echo "🧠 AUTO_POPULATE_COMIPEMS_RUNTIME=true, iniciando poblacion automatica..."
    echo "📊 Reactivos activos actuales: $CURRENT_QUESTIONS_VALUE | Minimo objetivo: $AUTO_POPULATE_MIN_ACTIVE_QUESTIONS_VALUE"

    OLD_IFS="$IFS"
    IFS=','

    for MATERIA in $AUTO_POPULATE_MATERIAS_VALUE; do
      MATERIA_TRIMMED="$(echo "$MATERIA" | sed 's/^ *//;s/ *$//')"

      if [ -z "$MATERIA_TRIMMED" ]; then
        continue
      fi

      echo "📚 Poblando materia: $MATERIA_TRIMMED (lotes: $AUTO_POPULATE_LOTES_VALUE)"
      if ! php artisan db:populate-comipems "$MATERIA_TRIMMED" "$AUTO_POPULATE_LOTES_VALUE"; then
        echo "⚠️ Fallo al poblar '$MATERIA_TRIMMED'. Continuando..."
      fi
    done

    IFS="$OLD_IFS"
  fi
else
  echo "⏭️ AUTO_POPULATE_COMIPEMS_RUNTIME=false, se omite poblacion automatica."
fi

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
apply_secure_permissions

echo "✅ NexusEdu está LIVE."
exec /usr/bin/supervisord -c /etc/supervisord.conf
