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
        $admin = User::firstOrCreate(
            ['email' => 'admin@nexusedu.test'],
            [
                'name' => 'Admin NexusEdu',
                'password' => Hash::make('password'),
                'onboarded_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Student Demo
        $demo = User::firstOrCreate(
            ['email' => 'demo@nexusedu.test'],
            [
                'name' => 'Demo Student',
                'password' => Hash::make('password'),
                'onboarded_at' => now(),
                'streak_days' => 15,
                'last_study_at' => now(),
                'preferences' => [
                    'area' => 1,
                    'career' => 'Ingeniería en Computación',
                ],
            ]
        );
        $demo->assignRole('student');

        // New Student
        $new = User::firstOrCreate(
            ['email' => 'nuevo@nexusedu.test'],
            [
                'name' => 'Nuevo Estudiante',
                'password' => Hash::make('password'),
                'onboarded_at' => null,
            ]
        );
        $new->assignRole('student');
    }
}
