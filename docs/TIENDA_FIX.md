# 🎁 Fix para la Tienda Vacía en Production

## Problema  
La tienda aparece vacía en production (Railway) porque las migraciones o el seeder no se ejecutaron.

## Solución Inmediata (Manual en Railway)

### Opción 1: Usar Railway CLI (Recomendado)
```bash
# 1. Instalar Railway CLI si no lo tienes
npm i -g @railway/cli

# 2. Conectar tu cuenta
railway link

# 3. Ejecutar el comando en el container de producción
railway run php artisan rewards:setup

# 4. Verifica el output - deberías ver algo como:
# 🎁 Verificando sistema de recompensas...
# ✅ Tabla 'reward_items' existe
# ✅ Tabla 'user_reward_items' existe
# ✅ Tabla 'user_reward_equips' existe
# ✅ Tabla 'reward_purchases' existe
# ✅ 9 items ya existen en la tienda
```

### Opción 2: Via Railway Dashboard
1. Ve a Railway Dashboard → Tu Proyecto
2. Entra al servicio de la app (NexusEdu)
3. Ve a la pestaña "Deploy"
4. Busca "Run command" o consola
5. Ejecuta: `php artisan rewards:setup`

### Opción 3: Trigger Manual via GitHub
1. Pushea un cambio trivial o usa un webhook
2. Railway redeploy automáticamente ejecutará las nuevas migraciones/seeders
3. O espera al próximo deploy

---

## Cambios Realizados Automáticamente

✅ **app/Console/Commands/SetupRewardSystem.php**
- Nuevo comando `php artisan rewards:setup`
- Verifica si las tablas existen
- Verifica si hay datos
- Ejecuta migraciones si faltan
- Ejecuta seeder si está vacío

✅ **docker/entrypoint.sh**
- Cambiado: `AUTO_RUN_SEEDERS=true` (antes era false)
- Agregado: Ejecución automática de `rewards:setup`
- Cada startup del container verifica la tienda

✅ **scripts/post-deploy.sh**
- Script bash para ejecutar manualmente
- Ejecuta migraciones + seeders + rewards:setup

---

## Verificación

### Local
```bash
php artisan rewards:setup
```

### Production
```bash
railway run php artisan rewards:setup
```

Ambos deberían mostrar:
```
📊 ESTADO DEL SISTEMA DE RECOMPENSAS:
   • Tablas: ✅ Listas
   • Items de tienda: 9
   • Estado: ✅ FUNCIONAL
```

---

## Próximos Deploys (Automático)

A partir del commit `64b0beb` en adelante, cada deploy automáticamente:
1. ✅ Ejecuta migraciones pendientes
2. ✅ Ejecuta seeders
3. ✅ Verifica y configura tienda

**No necesitarás hacer nada manualmente de ahora en adelante.**
