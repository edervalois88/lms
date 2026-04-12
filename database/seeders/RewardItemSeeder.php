<?php

namespace Database\Seeders;

use App\Models\RewardItem;
use Illuminate\Database\Seeder;

class RewardItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'code' => 'avatar_operator_basic',
                'name' => 'Avatar Operador',
                'category' => 'avatar',
                'slot' => 'avatar',
                'rarity' => 'common',
                'cost_xp' => 500,
                'metadata' => [
                    'icon_class' => 'fa-solid fa-user-astronaut',
                    'primary_color' => '#ff6b00',
                    'secondary_color' => '#ff9f43',
                ],
            ],
            [
                'code' => 'avatar_strategist_elite',
                'name' => 'Avatar Estratega Elite',
                'category' => 'avatar',
                'slot' => 'avatar',
                'rarity' => 'epic',
                'cost_xp' => 2000,
                'metadata' => [
                    'icon_class' => 'fa-solid fa-chess-knight',
                    'primary_color' => '#2563eb',
                    'secondary_color' => '#60a5fa',
                ],
            ],
            [
                'code' => 'avatar_surgeon_elite',
                'name' => 'Avatar Cirujano Elite',
                'category' => 'avatar',
                'slot' => 'avatar',
                'rarity' => 'legendary',
                'cost_xp' => 2000,
                'metadata' => [
                    'icon_class' => 'fa-solid fa-shield-halved',
                    'primary_color' => '#059669',
                    'secondary_color' => '#34d399',
                ],
            ],
            [
                'code' => 'theme_cyber_blue',
                'name' => 'Tema Azul Ciber',
                'category' => 'theme',
                'slot' => 'ui_theme',
                'rarity' => 'epic',
                'cost_xp' => 3500,
                'metadata' => [
                    'primary_color' => '#2563eb',
                    'secondary_color' => '#38bdf8',
                    'soft_color' => 'rgba(37, 99, 235, 0.18)',
                ],
            ],
            [
                'code' => 'theme_neon_green',
                'name' => 'Tema Verde Neon',
                'category' => 'theme',
                'slot' => 'ui_theme',
                'rarity' => 'epic',
                'cost_xp' => 3500,
                'metadata' => [
                    'primary_color' => '#059669',
                    'secondary_color' => '#84cc16',
                    'soft_color' => 'rgba(5, 150, 105, 0.18)',
                ],
            ],
            [
                'code' => 'title_estratega',
                'name' => 'Titulo Estratega',
                'category' => 'title',
                'slot' => 'profile_title',
                'rarity' => 'rare',
                'cost_xp' => 1200,
                'metadata' => [
                    'label' => 'ESTRATEGA',
                ],
            ],
            [
                'code' => 'title_cirujano_elite',
                'name' => 'Titulo Cirujano Elite',
                'category' => 'title',
                'slot' => 'profile_title',
                'rarity' => 'legendary',
                'cost_xp' => 2200,
                'metadata' => [
                    'label' => 'CIRUJANO ELITE',
                ],
            ],
            [
                'code' => 'frame_ion',
                'name' => 'Marco Ion',
                'category' => 'frame',
                'slot' => 'profile_frame',
                'rarity' => 'rare',
                'cost_xp' => 900,
                'metadata' => [
                    'border_color' => '#f97316',
                ],
            ],
            [
                'code' => 'frame_quantum',
                'name' => 'Marco Quantum',
                'category' => 'frame',
                'slot' => 'profile_frame',
                'rarity' => 'epic',
                'cost_xp' => 1600,
                'metadata' => [
                    'border_color' => '#38bdf8',
                ],
            ],
        ];

        foreach ($items as $item) {
            RewardItem::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}
