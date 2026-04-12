<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SubjectSeeder::class,
            TopicSeeder::class,
            OfficialQuestionBankSeeder::class,
            UserSeeder::class,
            AcademicOfferSeeder::class,
            VocationalQuestionSeeder::class,
            RewardItemSeeder::class,
        ]);
    }
}
