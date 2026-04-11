<?php

namespace App\Services\Vocational;

use App\Models\User;
use App\Models\VocationalResult;
use App\Models\VocationalQuestion;
use App\Models\Major;

class VocationalService
{
    /**
     * Procesa las respuestas del test y guarda el resultado.
     * $answers: [question_id => score (1-5)]
     */
    public function processResults(User $user, array $answers): VocationalResult
    {
        $questions = VocationalQuestion::whereIn('id', array_keys($answers))->get()->groupBy('ria_type');
        
        $scores = [
            'R' => 0, 'I' => 0, 'A' => 0, 'S' => 0, 'E' => 0, 'C' => 0
        ];

        foreach ($questions as $type => $group) {
            foreach ($group as $question) {
                $scores[$type] += $answers[$question->id] ?? 0;
            }
        }

        // Obtener las 3 letras dominantes
        arsort($scores);
        $primaryType = implode('', array_slice(array_keys($scores), 0, 3));

        return VocationalResult::create([
            'user_id' => $user->id,
            'scores' => $scores,
            'primary_type' => $primaryType,
            'recommendation' => $this->generateRecommendation($primaryType)
        ]);
    }

    /**
     * Recomienda carreras basadas en el código Holland y la universidad meta.
     */
    public function getRecommendedMajors(string $hollandCode, ?int $universityId = null)
    {
        $letters = str_split($hollandCode);
        
        $query = Major::query()->with(['campus.university']);

        // Buscamos carreras que contengan al menos una de las letras principales
        // En un sistema real, usaríamos una lógica de coincidencia más compleja
        $query->where(function ($q) use ($letters) {
            foreach ($letters as $letter) {
                $q->orWhere('holland_code', 'LIKE', "%{$letter}%");
            }
        });

        if ($universityId) {
            $query->whereHas('campus', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }

        return $query->limit(6)->get();
    }

    private function generateRecommendation(string $code): string
    {
        $descriptions = [
            'R' => 'Realista (Práctico/Técnico)',
            'I' => 'Investigador (Analítico/Científico)',
            'A' => 'Artístico (Creativo/Expresivo)',
            'S' => 'Social (Servicio/Enseñanza)',
            'E' => 'Emprendedor (Liderazgo/Negocios)',
            'C' => 'Convencional (Organizado/Detallista)',
        ];

        $letters = str_split($code);
        $text = "Tu perfil principal es **{$code}**. Sobresales en áreas: ";
        $areas = array_map(fn($l) => $descriptions[$l], $letters);
        
        return $text . implode(', ', $areas) . ".";
    }
}
