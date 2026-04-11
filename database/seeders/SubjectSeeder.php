<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            [
                'name' => 'Matemáticas',
                'description' => 'Álgebra, cálculo, geometría y trigonometría.',
                'exam_areas' => [1, 2],
                'color' => '#3B82F6',
                'icon' => 'calculator',
                'sort_order' => 1,
            ],
            [
                'name' => 'Física',
                'description' => 'Mecánica, termodinámica y electromagnetismo.',
                'exam_areas' => [1],
                'color' => '#8B5CF6',
                'icon' => 'zap',
                'sort_order' => 2,
            ],
            [
                'name' => 'Química',
                'description' => 'Estructura atómica, enlaces y reacciones.',
                'exam_areas' => [1, 2],
                'color' => '#EC4899',
                'icon' => 'flask',
                'sort_order' => 3,
            ],
            [
                'name' => 'Biología',
                'description' => 'Célula, genética, evolución y ecología.',
                'exam_areas' => [2],
                'color' => '#10B981',
                'icon' => 'leaf',
                'sort_order' => 4,
            ],
            [
                'name' => 'Historia Universal',
                'description' => 'Desde la Ilustración hasta el mundo actual.',
                'exam_areas' => [3, 4],
                'color' => '#F59E0B',
                'icon' => 'globe',
                'sort_order' => 5,
            ],
            [
                'name' => 'Historia de México',
                'description' => 'Independencia, reforma y revolución.',
                'exam_areas' => [3, 4],
                'color' => '#EF4444',
                'icon' => 'flag',
                'sort_order' => 6,
            ],
            [
                'name' => 'Español',
                'description' => 'Comprensión lectora, gramática y ortografía.',
                'exam_areas' => [1, 2, 3, 4],
                'color' => '#6366F1',
                'icon' => 'book',
                'sort_order' => 7,
            ],
            [
                'name' => 'Literatura',
                'description' => 'Géneros literarios y corrientes universales.',
                'exam_areas' => [4],
                'color' => '#14B8A6',
                'icon' => 'feather',
                'sort_order' => 8,
            ],
            [
                'name' => 'Geografía',
                'description' => 'Geografía física, humana y económica.',
                'exam_areas' => [3],
                'color' => '#84CC16',
                'icon' => 'map',
                'sort_order' => 9,
            ],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['name' => $subject['name']],
                array_merge($subject, ['slug' => Str::slug($subject['name'])])
            );
        }
    }
}
