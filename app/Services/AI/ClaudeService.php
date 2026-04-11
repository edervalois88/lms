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
            return $this->getMockQuestion($subject, $topic);
        }

        return json_decode($response, true) ?? $this->getMockQuestion($subject, $topic);
    }

    public function analyzeProfile(array $performanceData): array
    {
        $prompt = str_replace('{data}', json_encode($performanceData), self::PROFILE_ANALYSIS_PROMPT);
        $response = $this->callClaude($prompt, "Analiza el perfil.");

        if (!$response) {
            return $this->getMockAnalysis($performanceData);
        }

        return json_decode($response, true) ?? $this->getMockAnalysis($performanceData);
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

    private function getMockQuestion(string $subject, string $topic): array
    {
        return [
            'body' => "¿Cual es el resultado de resolver la ecuación 2x + 5 = 15?",
            'options' => ["x = 5", "x = 10", "x = 7.5", "x = 2"],
            'correct_index' => 0,
            'explanation' => "Restamos 5 de ambos lados: 2x = 10. Luego dividimos entre 2: x = 5.",
            'concept' => "Ecuaciones de primer grado"
        ];
    }

    private function getMockAnalysis(array $data): array
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
