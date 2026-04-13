<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Learning\GamificationService;
use Illuminate\Console\Command;

class AddXpToUser extends Command
{
    protected $signature = 'users:add-xp {user_email_or_name} {amount} {--reason=admin_gift}';
    protected $description = 'Agrega XP a un usuario especificado';

    public function handle(GamificationService $gamification): int
    {
        $userInput = $this->argument('user_email_or_name');
        $amount = (int) $this->argument('amount');
        $reason = $this->option('reason') ?? 'admin_gift';

        if ($amount <= 0) {
            $this->error('❌ La cantidad de XP debe ser mayor a 0.');
            return self::FAILURE;
        }

        // Buscar usuario por email o nombre
        $user = User::query()
            ->where('email', $userInput)
            ->orWhere('name', $userInput)
            ->first();

        if (!$user) {
            $this->error("❌ No se encontró usuario: '{$userInput}'");
            return self::FAILURE;
        }

        $this->info("👤 Usuario encontrado: {$user->name} ({$user->email})");

        // Obtener XP actual
        $xpBefore = $gamification->getCurrentXp($user);
        $this->line("   XP actual: {$xpBefore}");

        // Agregar XP
        $result = $gamification->earnXp($user, $amount, $reason, [
            'admin_note' => 'Carga manual desde command',
            'timestamp' => now(),
        ]);

        if (!($result['earned'] ?? 0)) {
            $this->error('❌ Falló al agregar XP');
            return self::FAILURE;
        }

        $xpAfter = $gamification->getCurrentXp($user);
        
        $this->info('');
        $this->info('✅ XP CARGADO EXITOSAMENTE');
        $this->info("   • Usuario: {$user->name}");
        $this->info("   • XP Anterior: {$xpBefore}");
        $this->info("   • XP Agregado: {$amount}");
        $this->info("   • XP Nuevo: {$xpAfter}");
        
        // Calcular nivel
        $level = $gamification->getLevel($user);
        $this->line("   • Nivel: {$level['current']} (Progreso: {$level['progress']}%)");

        return self::SUCCESS;
    }
}
