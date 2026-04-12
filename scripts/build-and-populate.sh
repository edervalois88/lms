#!/bin/sh
set -eu

echo "🚀 Build pipeline iniciado"

AUTO_POPULATE_COMIPEMS_VALUE="${AUTO_POPULATE_COMIPEMS:-false}"
AUTO_POPULATE_LOTES_VALUE="${AUTO_POPULATE_LOTES:-2}"
AUTO_POPULATE_MATERIAS_VALUE="${AUTO_POPULATE_MATERIAS:-Matemáticas,Física,Química,Biología,Historia Universal,Historia de México,Español,Geografía,Formación Cívica y Ética}"

if [ "$AUTO_POPULATE_COMIPEMS_VALUE" = "true" ]; then
  echo "🧠 AUTO_POPULATE_COMIPEMS=true, ejecutando población automática"

  OLD_IFS="$IFS"
  IFS=','

  for MATERIA in $AUTO_POPULATE_MATERIAS_VALUE; do
    MATERIA_TRIMMED="$(echo "$MATERIA" | sed 's/^ *//;s/ *$//')"

    if [ -z "$MATERIA_TRIMMED" ]; then
      continue
    fi

    echo "📚 Poblando materia: $MATERIA_TRIMMED (lotes: $AUTO_POPULATE_LOTES_VALUE)"

    if ! php artisan db:populate-comipems "$MATERIA_TRIMMED" "$AUTO_POPULATE_LOTES_VALUE"; then
      echo "⚠️ Falló población para '$MATERIA_TRIMMED'. Continuando con la siguiente materia."
    fi
  done

  IFS="$OLD_IFS"
else
  echo "⏭️ AUTO_POPULATE_COMIPEMS=false, se omite población automática"
fi

echo "🏗️ Compilando assets frontend"
npm run build:assets

echo "✅ Build pipeline completado"
