<?php

namespace App\Console\Commands;

use App\Models\RewardItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SetupRewardSystem extends Command
{
    protected $signature = 'rewards:setup';
    protected $description = 'Verifica y configura el sistema de recompensas/tienda';

    public function handle(): int
    {
        $this->info('🎁 Verificando sistema de recompensas...');

        // Paso 1: Verificar si las tablas existen
        $tablesNeeded = ['reward_items', 'user_reward_items', 'user_reward_equips', 'reward_purchases'];
        $missingTables = [];

        foreach ($tablesNeeded as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("❌ Tabla '$table' NO existe");
                $missingTables[] = $table;
            } else {
                $this->line("✅ Tabla '$table' existe");
            }
        }

        // Paso 2: Si faltan tablas, ejecutar migraciones
        if (!empty($missingTables)) {
            $this->info("\n📦 Ejecutando migraciones pendientes...");
            $this->call('migrate', ['--force' => true]);
            $this->info("✅ Migraciones completadas");
        }

        // Paso 3: Verificar datos en reward_items
        $itemCount = 0;
        if (Schema::hasTable('reward_items')) {
            $itemCount = RewardItem::count();
            if ($itemCount === 0) {
                $this->warn("\n⚠️  No hay items en la tienda. Ejecutando seeder...");
                $this->call('db:seed', ['--class' => 'Database\\Seeders\\RewardItemSeeder']);
                $itemCount = RewardItem::count();
                $this->info("✅ {$itemCount} items cargados en la tienda");
            } else {
                $this->line("\n✅ {$itemCount} items ya existen en la tienda");
            }
        }

        // Paso 4: Resumen
        $this->info("\n" . str_repeat('─', 50));
        $this->info('📊 ESTADO DEL SISTEMA DE RECOMPENSAS:');
        $this->line("   • Tablas: " . (count($missingTables) === 0 ? "✅ Listas" : "❌ Algunas faltaban"));
        $this->line("   • Items de tienda: {$itemCount}");
        $this->line("   • Estado: " . ($itemCount > 0 ? "✅ FUNCIONAL" : "❌ REQUIERE ATENCIÓN"));
        $this->info(str_repeat('─', 50));

        return $itemCount > 0 ? 0 : 1;
    }
}
