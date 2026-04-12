<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class OfficialQuestionBankSeeder extends Seeder
{
    public function run(): void
    {
        $bank = [
            ['subject' => 'Matemáticas', 'topic' => 'Álgebra', 'stem' => 'Si 3x - 5 = 16, ¿cuál es el valor de x?', 'options' => ['7', '6', '5', '8'], 'correct' => '7', 'explanation' => 'Sumamos 5 en ambos lados: 3x = 21. Luego dividimos entre 3: x = 7.', 'difficulty' => 3],
            ['subject' => 'Matemáticas', 'topic' => 'Funciones', 'stem' => 'Si f(x)=2x+1, ¿cuánto vale f(4)?', 'options' => ['9', '8', '7', '10'], 'correct' => '9', 'explanation' => 'Sustituimos x=4: f(4)=2(4)+1=9.', 'difficulty' => 2],
            ['subject' => 'Matemáticas', 'topic' => 'Geometría Analítica', 'stem' => '¿Cuál es la pendiente de la recta que pasa por (1,2) y (3,6)?', 'options' => ['2', '1', '3', '4'], 'correct' => '2', 'explanation' => 'm=(6-2)/(3-1)=4/2=2.', 'difficulty' => 4],
            ['subject' => 'Física', 'topic' => 'Cinemática', 'stem' => 'Un móvil recorre 60 m en 12 s con velocidad constante. ¿Cuál es su velocidad?', 'options' => ['5 m/s', '6 m/s', '4 m/s', '12 m/s'], 'correct' => '5 m/s', 'explanation' => 'v=d/t=60/12=5 m/s.', 'difficulty' => 2],
            ['subject' => 'Física', 'topic' => 'Dinámica', 'stem' => 'Si una fuerza neta de 20 N actúa sobre una masa de 4 kg, ¿cuál es su aceleración?', 'options' => ['5 m/s²', '4 m/s²', '6 m/s²', '8 m/s²'], 'correct' => '5 m/s²', 'explanation' => 'Por la segunda ley de Newton: a=F/m=20/4=5 m/s².', 'difficulty' => 3],
            ['subject' => 'Física', 'topic' => 'Termodinámica', 'stem' => '¿Qué magnitud se conserva en una transferencia de calor en sistema aislado?', 'options' => ['Energía', 'Temperatura', 'Volumen', 'Presión'], 'correct' => 'Energía', 'explanation' => 'En un sistema aislado se conserva la energía total, aunque cambie su forma.', 'difficulty' => 4],
            ['subject' => 'Química', 'topic' => 'Estructura Atómica', 'stem' => '¿Qué partícula subatómica tiene carga negativa?', 'options' => ['Electrón', 'Protón', 'Neutrón', 'Positrón'], 'correct' => 'Electrón', 'explanation' => 'El electrón tiene carga negativa; el protón positiva y el neutrón neutra.', 'difficulty' => 1],
            ['subject' => 'Química', 'topic' => 'Tabla Periódica', 'stem' => '¿Qué propiedad aumenta de izquierda a derecha en un periodo?', 'options' => ['Electronegatividad', 'Radio atómico', 'Carácter metálico', 'Número de capas'], 'correct' => 'Electronegatividad', 'explanation' => 'En general, la electronegatividad aumenta hacia la derecha en un periodo.', 'difficulty' => 4],
            ['subject' => 'Química', 'topic' => 'Reacciones Químicas', 'stem' => 'En una reacción de combustión completa de hidrocarburos se produce principalmente:', 'options' => ['CO2 y H2O', 'CO y H2', 'C y O2', 'H2O2 y CO'], 'correct' => 'CO2 y H2O', 'explanation' => 'La combustión completa genera dióxido de carbono y agua.', 'difficulty' => 2],
            ['subject' => 'Biología', 'topic' => 'La Célula', 'stem' => '¿Qué organelo es el principal responsable de la producción de ATP?', 'options' => ['Mitocondria', 'Ribosoma', 'Lisosoma', 'Aparato de Golgi'], 'correct' => 'Mitocondria', 'explanation' => 'La mitocondria realiza respiración celular y síntesis de ATP.', 'difficulty' => 2],
            ['subject' => 'Biología', 'topic' => 'Genética', 'stem' => '¿Cómo se llama una versión alternativa de un gen?', 'options' => ['Alelo', 'Cromátida', 'Locus', 'Fenotipo'], 'correct' => 'Alelo', 'explanation' => 'Un alelo es una variante de un mismo gen.', 'difficulty' => 3],
            ['subject' => 'Biología', 'topic' => 'Evolución', 'stem' => 'La selección natural favorece principalmente a los individuos que:', 'options' => ['Dejan más descendencia viable', 'Son más grandes', 'Viven más años', 'Mutan más rápido'], 'correct' => 'Dejan más descendencia viable', 'explanation' => 'La aptitud evolutiva se mide por el éxito reproductivo.', 'difficulty' => 4],
            ['subject' => 'Historia Universal', 'topic' => 'La Ilustración y Revoluciones Liberales', 'stem' => '¿Qué idea política impulsó la Ilustración?', 'options' => ['Soberanía popular', 'Derecho divino de los reyes', 'Feudalismo', 'Mercantilismo estricto'], 'correct' => 'Soberanía popular', 'explanation' => 'La Ilustración promovió razón, derechos y soberanía del pueblo.', 'difficulty' => 3],
            ['subject' => 'Historia Universal', 'topic' => 'La Guerra Fría', 'stem' => '¿Qué bloque político lideró Estados Unidos durante la Guerra Fría?', 'options' => ['Occidental/capitalista', 'Socialista', 'No alineado', 'Imperial austrohúngaro'], 'correct' => 'Occidental/capitalista', 'explanation' => 'EE.UU. encabezó el bloque occidental frente a la URSS.', 'difficulty' => 2],
            ['subject' => 'Historia de México', 'topic' => 'La Independencia de México', 'stem' => '¿En qué año inició formalmente la Independencia de México?', 'options' => ['1810', '1821', '1808', '1830'], 'correct' => '1810', 'explanation' => 'El proceso inició en 1810 con el Grito de Dolores.', 'difficulty' => 2],
            ['subject' => 'Historia de México', 'topic' => 'La Revolución Mexicana', 'stem' => '¿Qué documento sintetiza demandas agrarias revolucionarias de 1911?', 'options' => ['Plan de Ayala', 'Constitución de Cádiz', 'Plan de San Luis de 1917', 'Tratado de Guadalupe'], 'correct' => 'Plan de Ayala', 'explanation' => 'El Plan de Ayala (Zapata) exigía restitución de tierras.', 'difficulty' => 4],
            ['subject' => 'Español', 'topic' => 'Comprensión de Lectura', 'stem' => 'Identificar la idea principal de un texto implica reconocer:', 'options' => ['El tema central que organiza la información', 'La palabra más repetida', 'La oración más larga', 'La opinión del lector'], 'correct' => 'El tema central que organiza la información', 'explanation' => 'La idea principal articula el contenido global del texto.', 'difficulty' => 3],
            ['subject' => 'Español', 'topic' => 'Ortografía y Puntuación', 'stem' => '¿Cuál opción está correctamente acentuada?', 'options' => ['También', 'Tambien', 'Tambíen', 'Tam bien'], 'correct' => 'También', 'explanation' => 'También lleva tilde por ser palabra aguda terminada en n.', 'difficulty' => 2],
            ['subject' => 'Literatura', 'topic' => 'El Texto Literario y Géneros', 'stem' => '¿Qué género literario se caracteriza por la representación escénica?', 'options' => ['Dramático', 'Narrativo', 'Lírico', 'Didáctico'], 'correct' => 'Dramático', 'explanation' => 'El género dramático está pensado para ser representado.', 'difficulty' => 2],
            ['subject' => 'Geografía', 'topic' => 'Población y Migración', 'stem' => '¿Cuál es una causa frecuente de migración interna?', 'options' => ['Búsqueda de empleo', 'Disminución de la temperatura global', 'Rotación de la Tierra', 'Cambio de huso horario'], 'correct' => 'Búsqueda de empleo', 'explanation' => 'El trabajo y oportunidades económicas son causas principales.', 'difficulty' => 2],
        ];

        foreach ($bank as $item) {
            $topic = Topic::query()
                ->where('name', $item['topic'])
                ->whereHas('subject', function ($query) use ($item) {
                    $query->where('name', $item['subject']);
                })
                ->first();

            if (! $topic) {
                continue;
            }

            Question::updateOrCreate(
                [
                    'topic_id' => $topic->id,
                    'stem' => $item['stem'],
                ],
                [
                    'created_by' => null,
                    'type' => 'multiple_choice',
                    'options' => $item['options'],
                    'correct_answer' => $item['correct'],
                    'explanation' => $item['explanation'],
                    'difficulty' => $item['difficulty'],
                    'is_ai_generated' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}
