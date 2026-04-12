<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    public const QUESTION_GENERATION_PROMPT = 'Genera una pregunta de opción múltiple para el examen de la UNAM sobre el tema: "{topic}" de la materia de "{subject}" con dificultad {difficulty} (1-10). Retorna un JSON con: body, options (array de 4), correct_index (0-3), explanation y concept.';
    public const PROFILE_ANALYSIS_PROMPT = 'Analiza el siguiente historial de desempeño académico y proyecta el resultado en el examen UNAM: {data}. Retorna un JSON con mastery por materia, áreas críticas, fortalezas, proyección de puntaje, plan de estudio y mensaje motivacional.';
    public const ANSWER_EXPLANATION_PROMPT = 'Explica de forma pedagógica por qué la opción seleccionada es incorrecta y por qué la correcta es la acertada para la pregunta: "{question}". Opciones: {options}. Correcta: {correct}. Seleccionada: {selected}.';
    public const WEEKLY_RECOMMENDATION_PROMPT = 'Basado en las estadísticas de la semana: {stats}, genera una recomendación de estudio breve y accionable para un estudiante que aspira a la UNAM.';
    public const ALTERNATIVE_CAREERS_PROMPT = 'Basado en que el estudiante aspira a "{target_major}" (Puntaje meta: {target_score}) pero su proyección actual es de {current_score} aciertos, sugiere 3 carreras alternativas de la misma área que tengan un puntaje de corte menor pero afinidad académica relevante. Retorna un JSON con un array de objetos (name, reason).';

    public function suggestAlternatives(string $majorName, int $targetScore, int $currentScore): array
    {
        $prompt = str_replace(
            ['{target_major}', '{target_score}', '{current_score}'],
            [$majorName, $targetScore, $currentScore],
            self::ALTERNATIVE_CAREERS_PROMPT
        );

        $response = $this->callClaude($prompt, "Sugiere alternativas.");

        return json_decode($response, true) ?? [
            ['name' => 'Carrera similar en FES', 'reason' => 'Suele tener puntajes de corte más accesibles conservando el mismo plan de estudios.'],
            ['name' => 'Licenciatura de Area afin', 'reason' => 'Comparte tronco común y permite cambio interno posteriormente.']
        ];
    }

    public function generateQuestion(string $subject, string $topic, int $difficulty): array
    {
        $prompt = str_replace(['{subject}', '{topic}', '{difficulty}'], [$subject, $topic, $difficulty], self::QUESTION_GENERATION_PROMPT);
        
        $response = $this->callClaude($prompt, "Genera la pregunta ahora.");

        if (!$response) {
            return $this->getFallbackQuestion($subject, $topic);
        }

        return json_decode($response, true) ?? $this->getFallbackQuestion($subject, $topic);
    }

    public function analyzeProfile(array $performanceData): array
    {
        $prompt = str_replace('{data}', json_encode($performanceData), self::PROFILE_ANALYSIS_PROMPT);
        $response = $this->callClaude($prompt, "Analiza el perfil.");

        if (!$response) {
            return $this->getFallbackAnalysis($performanceData);
        }

        return json_decode($response, true) ?? $this->getFallbackAnalysis($performanceData);
    }

    public function explainAnswer(string $questionBody, array $options, int $correctIndex, int $selectedIndex): string
    {
        $prompt = str_replace(
            ['{question}', '{options}', '{correct}', '{selected}'],
            [$questionBody, json_encode($options), $options[$correctIndex], $options[$selectedIndex] ?? 'N/A'],
            self::ANSWER_EXPLANATION_PROMPT
        );

        $response = $this->callClaude($prompt, "Explica la respuesta.");

        return $response ?: "Lo sentimos, no pudimos generar la explicación en este momento. La respuesta correcta es la opción " . ($correctIndex + 1) . ".";
    }

    public function getWeeklyRecommendation(array $stats): string
    {
        $prompt = str_replace('{stats}', json_encode($stats), self::WEEKLY_RECOMMENDATION_PROMPT);
        $response = $this->callClaude($prompt, "Genera recomendación.");

        return $response ?: "Esta semana te recomendamos enfocarte en las materias con menor porcentaje de aciertos. ¡Sigue así!";
    }

    private function callClaude(string $systemPrompt, string $userMessage): ?string
    {
        $apiKey = config('services.anthropic.key');
        
        if (!$apiKey) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model'),
                'max_tokens' => 1024,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage]
                ]
            ]);

            return $response->json('content.0.text');
        } catch (\Exception $e) {
            Log::error("Claude API Error: " . $e->getMessage());
            return null;
        }
    }

    private function getFallbackQuestion(string $subject, string $topic): array
    {
        $topicLabel = mb_strtolower($topic);

        if (str_contains($topicLabel, 'álgebra') || str_contains($topicLabel, 'algebra')) {
            return [
                'body' => 'Si 2x + 3 = 11, ¿cuál es el valor de x?',
                'options' => ['4', '3', '5', '2'],
                'correct_index' => 0,
                'explanation' => 'Restamos 3 a ambos lados: 2x = 8. Dividimos entre 2: x = 4.',
                'concept' => 'Ecuaciones lineales',
            ];
        }

        if (str_contains($topicLabel, 'cinemática') || str_contains($topicLabel, 'cinematica')) {
            return [
                'body' => 'Un objeto recorre 100 m en 20 s. ¿Cuál es su velocidad promedio?',
                'options' => ['5 m/s', '4 m/s', '10 m/s', '2 m/s'],
                'correct_index' => 0,
                'explanation' => 'La velocidad promedio se calcula como distancia entre tiempo: 100/20 = 5 m/s.',
                'concept' => 'Velocidad promedio',
            ];
        }

        return [
            'body' => "¿Cuál enunciado describe mejor un concepto central de {$topic} en {$subject}?",
            'options' => [
                'La opción que coincide con la definición formal del tema',
                'Una afirmación parcialmente correcta sin sustento conceptual',
                'Una idea que contradice el marco teórico de la materia',
                'Una frase ambigua sin relación directa con el temario',
            ],
            'correct_index' => 0,
            'explanation' => "La opción correcta es la que respeta la definición académica aceptada para {$topic} y su aplicación en reactivos tipo UNAM.",
            'concept' => $topic,
        ];
    }

    private function getFallbackAnalysis(array $data): array
    {
        return [
            'subject_mastery' => [['subject' => 'Matemáticas', 'score' => 85]],
            'critical_areas' => ["Cálculo Integral"],
            'strengths' => ["Álgebra"],
            'score_projection' => 105,
            'study_plan' => "Enfocarse en derivaciones e integraciones básicas.",
            'motivational_message' => "¡Vas por excelente camino! Tu constancia te llevará a la UNAM."
        ];
    }
}
