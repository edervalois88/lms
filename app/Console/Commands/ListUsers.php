<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
    protected $signature = 'users:list';
    protected $description = 'Lista todos los usuarios en la base de datos';

    public function handle(): int
    {
        $users = User::select('id', 'name', 'email')->get();

        if ($users->isEmpty()) {
            $this->warn('⚠️ No hay usuarios en la base de datos');
            return self::SUCCESS;
        }

        $this->info('📋 USUARIOS EN LA BASE DE DATOS:');
        $this->line(str_repeat('─', 70));
        
        foreach ($users as $user) {
            $this->line("  ID: {$user->id} | {$user->name} | {$user->email}");
        }
        
        $this->line(str_repeat('─', 70));
        $this->info("Total: {$users->count()} usuario(s)");

        return self::SUCCESS;
    }
}
