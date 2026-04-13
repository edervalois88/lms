<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Learning\GamificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUserWithXp extends Command
{
    protected $signature = 'users:create-with-xp {email} {name} {xp=0}';
    protected $description = 'Crea un usuario y le asigna XP inicial';

    public function handle(GamificationService $gamification): int
    {
        $email = $this->argument('email');
        $name = $this->argument('name');
        $xp = (int) $this->argument('xp');

        $this->info("👤 Creando usuario: {$name} ({$email})");

        // Crear o recuperar usuario
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'onboarded_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->line("   ✅ Usuario creado exitosamente");
        } else {
            $this->line("   ℹ️ Usuario ya existía");
        }

        // Asignar XP
        if ($xp > 0) {
            $xpBefore = $gamification->getCurrentXp($user);
            $result = $gamification->earnXp($user, $xp, 'initial_setup', [
                'reason' => 'User creation with initial XP',
            ]);

            $xpAfter = $gamification->getCurrentXp($user);
            $level = $gamification->getLevel($user);

            $this->info('');
            $this->info('✅ USUARIO LISTO');
            $this->info("   • Nombre: {$user->name}");
            $this->info("   • Email: {$user->email}");
            $this->info("   • XP Inicial: {$xpBefore}");
            $this->info("   • XP Agregado: {$xp}");
            $this->info("   • XP Total: {$xpAfter}");
            $this->info("   • Nivel: {$level['current']} (Progreso: {$level['progress']}%)");
        }

        return self::SUCCESS;
    }
}
