<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topicsData = [
            'Matemáticas' => [
                'Álgebra',
                'Funciones',
                'Geometría Analítica',
                'Cálculo Diferencial',
                'Cálculo Integral',
            ],
            'Física' => [
                'Cinemática',
                'Dinámica',
                'Termodinámica',
                'Ondas',
                'Electromagnetismo',
            ],
            'Química' => [
                'Estructura Atómica',
                'Tabla Periódica',
                'Enlace Químico',
                'Reacciones Químicas',
                'Química Orgánica',
            ],
            'Biología' => [
                'La Célula',
                'Metabolismo Celular',
                'Genética',
                'Evolución',
                'Ecología',
            ],
            'Historia Universal' => [
                'La Ilustración y Revoluciones Liberales',
                'El Imperialismo y Primera Guerra Mundial',
                'Periodo de Entre Guerras y Segunda Guerra Mundial',
                'La Guerra Fría',
                'El Mundo Actual',
            ],
            'Historia de México' => [
                'La Nueva España',
                'La Independencia de México',
                'La Reforma y el Porfiriato',
                'La Revolución Mexicana',
                'México Contemporáneo',
            ],
            'Español' => [
                'Funciones de la Lengua',
                'Formas del Discurso',
                'Comprensión de Lectura',
                'Gramática',
                'Ortografía y Puntuación',
            ],
            'Literatura' => [
                'El Texto Literario y Géneros',
                'Corrientes Literarias',
                'Literatura Contemporánea',
                'Redacción y Estilo',
                'El Texto Dramático',
            ],
            'Geografía' => [
                'El Espacio Geográfico y Mapas',
                'Dinámica de la Litosfera y Relieve',
                'Climas y Regiones Naturales',
                'Población y Migración',
                'Espacios Económicos y Globalización',
            ],
        ];

        foreach ($topicsData as $subjectName => $topics) {
            $subject = Subject::where('name', $subjectName)->first();
            
            if ($subject) {
                foreach ($topics as $index => $topicName) {
                    Topic::create([
                        'subject_id' => $subject->id,
                        'name' => $topicName,
                        'slug' => Str::slug($topicName),
                        'difficulty_base' => rand(2, 4),
                        'sort_order' => $index + 1,
                    ]);
                }
            }
        }
    }
}
