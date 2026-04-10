<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin NexusEdu',
            'email' => 'admin@nexusedu.test',
            'password' => Hash::make('password'),
            'onboarded_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Student Demo
        $demo = User::create([
            'name' => 'Demo Student',
            'email' => 'demo@nexusedu.test',
            'password' => Hash::make('password'),
            'onboarded_at' => now(),
            'streak_days' => 15,
            'last_study_at' => now(),
            'preferences' => [
                'area' => 1,
                'career' => 'Ingeniería en Computación',
            ],
        ]);
        $demo->assignRole('student');

        // New Student
        $new = User::create([
            'name' => 'Nuevo Estudiante',
            'email' => 'nuevo@nexusedu.test',
            'password' => Hash::make('password'),
            'onboarded_at' => null,
        ]);
        $new->assignRole('student');
    }
}
