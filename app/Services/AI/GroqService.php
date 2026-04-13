<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    public const QUESTION_GENERATION_PROMPT = 'Genera una pregunta de opción múltiple para el examen de la UNAM sobre el tema: "{topic}" de la materia de "{subject}" con dificultad {difficulty} (1-10). Retorna un JSON con: body, options (array de 4), correct_index (0-3), explanation y concept.';
    public const PROFILE_ANALYSIS_PROMPT = 'Analiza el siguiente historial de desempeño académico y proyecta el resultado en el examen UNAM: {data}. Retorna un JSON con mastery por materia, áreas críticas, fortalezas, proyección de puntaje, plan de estudio y mensaje motivacional.';
    public const ANSWER_EXPLANATION_PROMPT = 'Explica de forma pedagógica por qué la opción seleccionada es incorrecta y por qué la correcta es la acertada para la pregunta: "{question}". Opciones: {options}. Correcta: {correct}. Seleccionada: {selected}.';
    public const WEEKLY_RECOMMENDATION_PROMPT = 'Basado en las estadísticas de la semana: {stats}, genera una recomendación de estudio breve y accionable para un estudiante que aspira a la UNAM.';
    public const ALTERNATIVE_CAREERS_PROMPT = 'Basado en que el estudiante aspira a "{target_major}" (Puntaje meta: {target_score}) pero su proyección actual es de {current_score} aciertos, sugiere 3 carreras alternativas de la misma área que tengan un puntaje de corte menor pero afinidad académica relevante. Retorna un JSON con un array de objetos (name, reason).';

    public function suggestAlternatives(string $majorName, int $targetScore, int $currentScore): array
    {
        $cacheKey = sprintf('groq:alternatives:%s', md5(json_encode([$majorName, $targetScore, $currentScore])));
        $ttl = (int) config('services.groq.cache_ttl_seconds', 900);

        return $this->rememberWithMetrics($cacheKey, $ttl, 'alternatives', function () use ($majorName, $targetScore, $currentScore) {
        $prompt = str_replace(
            ['{target_major}', '{target_score}', '{current_score}'],
            [$majorName, $targetScore, $currentScore],
            self::ALTERNATIVE_CAREERS_PROMPT
        );

        $response = $this->callGroq($prompt, 'Sugiere alternativas.', (int) config('services.groq.max_tokens_small', 140), 'alternatives');

        return json_decode($response, true) ?? [
            ['name' => 'Carrera similar en FES', 'reason' => 'Suele tener puntajes de corte más accesibles conservando el mismo plan de estudios.'],
            ['name' => 'Licenciatura de Area afin', 'reason' => 'Comparte tronco común y permite cambio interno posteriormente.'],
        ];
        });
    }

    public function generateQuestion(string $subject, string $topic, int $difficulty): array
    {
        $prompt = str_replace(['{subject}', '{topic}', '{difficulty}'], [$subject, $topic, $difficulty], self::QUESTION_GENERATION_PROMPT);
        $response = $this->callGroq($prompt, 'Genera la pregunta ahora.', (int) config('services.groq.max_tokens_medium', 220), 'question_generation');

        if (! $response) {
            return $this->getFallbackQuestion($subject, $topic);
        }

        return json_decode($response, true) ?? $this->getFallbackQuestion($subject, $topic);
    }

    public function analyzeProfile(array $performanceData): array
    {
        $cacheKey = sprintf('groq:analyze-profile:%s', md5(json_encode($performanceData, JSON_UNESCAPED_UNICODE)));
        $ttl = (int) config('services.groq.cache_ttl_seconds', 900);

        return $this->rememberWithMetrics($cacheKey, $ttl, 'analyze_profile', function () use ($performanceData) {
            $prompt = str_replace('{data}', json_encode($performanceData, JSON_UNESCAPED_UNICODE), self::PROFILE_ANALYSIS_PROMPT);
            $response = $this->callGroq($prompt, 'Analiza el perfil.', (int) config('services.groq.max_tokens_medium', 220), 'analyze_profile');

            if (! $response) {
                return $this->getFallbackAnalysis($performanceData);
            }

            return json_decode($response, true) ?? $this->getFallbackAnalysis($performanceData);
        });
    }

    public function explainAnswer(string $questionBody, array $options, int $correctIndex, int $selectedIndex): string
    {
        $prompt = str_replace(
            ['{question}', '{options}', '{correct}', '{selected}'],
            [$questionBody, json_encode($options), $options[$correctIndex], $options[$selectedIndex] ?? 'N/A'],
            self::ANSWER_EXPLANATION_PROMPT
        );

        $response = $this->callGroq($prompt, 'Explica la respuesta.', (int) config('services.groq.max_tokens_small', 140), 'explain_answer');

        return $response ?: 'Lo sentimos, no pudimos generar la explicación en este momento. La respuesta correcta es la opción ' . ($correctIndex + 1) . '.';
    }

    public function getWeeklyRecommendation(array $stats): string
    {
        $cacheKey = sprintf('groq:weekly-recommendation:%s', md5(json_encode($stats, JSON_UNESCAPED_UNICODE)));
        $ttl = (int) config('services.groq.cache_ttl_seconds', 900);

        return $this->rememberWithMetrics($cacheKey, $ttl, 'weekly_recommendation', function () use ($stats) {
            $prompt = str_replace('{stats}', json_encode($stats, JSON_UNESCAPED_UNICODE), self::WEEKLY_RECOMMENDATION_PROMPT);
            $response = $this->callGroq($prompt, 'Genera recomendación.', (int) config('services.groq.max_tokens_small', 140), 'weekly_recommendation');

            return $response ?: 'Esta semana te recomendamos enfocarte en las materias con menor porcentaje de aciertos. ¡Sigue así!';
        });
    }

    public function recommendWeakTopicPriorities(array $weakTopics, ?string $goalMajor = null): array
    {
        if (empty($weakTopics)) {
            return [];
        }

        $payload = [
            'goal_major' => $goalMajor,
            'weak_topics' => $weakTopics,
        ];

        $systemPrompt = 'Eres un tutor académico para examen UNAM. Recibirás temas débiles con score de dominio (0.0 a 1.0) y materia. Debes devolver SOLO JSON válido con esta forma exacta: {"priority_topics": ["tema1", "tema2", "tema3", "tema4", "tema5"]}. Ordena de mayor prioridad a menor para mejorar puntaje rápidamente. No agregues texto fuera del JSON.';
        $cacheKey = sprintf('groq:weak-topics:%s', md5(json_encode($payload, JSON_UNESCAPED_UNICODE)));
        $ttl = (int) config('services.groq.cache_ttl_seconds', 900);

        return $this->rememberWithMetrics($cacheKey, $ttl, 'weak_topic_priorities', function () use ($systemPrompt, $payload) {
            $response = $this->callGroq(
                $systemPrompt,
                'Datos: ' . json_encode($payload, JSON_UNESCAPED_UNICODE),
                (int) config('services.groq.max_tokens_small', 140),
                'weak_topic_priorities'
            );

            if (! $response) {
                return [];
            }

            $decoded = json_decode($response, true);

            if (! is_array($decoded)) {
                $jsonSlice = $this->extractJsonObject($response);
                $decoded = $jsonSlice ? json_decode($jsonSlice, true) : null;
            }

            if (! is_array($decoded)) {
                return [];
            }

            return collect((array) ($decoded['priority_topics'] ?? []))
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->map(fn ($value) => trim($value))
                ->take(8)
                ->values()
                ->all();
        });
    }

    private function callGroq(string $systemPrompt, string $userMessage, int $maxTokens, string $operation): ?string
    {
        $apiKey = config('services.groq.key');

        if (! $apiKey) {
            return null;
        }

        $baseUrl = rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $model = (string) config('services.groq.model', 'llama-3.1-8b-instant');
        $timeoutSeconds = (int) config('services.groq.timeout_seconds', 12);
        $retryTimes = (int) config('services.groq.retry_times', 1);
        $retrySleepMs = (int) config('services.groq.retry_sleep_ms', 250);

        try {
            $response = Http::withToken($apiKey)
                ->timeout($timeoutSeconds)
                ->retry($retryTimes, $retrySleepMs)
                ->acceptJson()
                ->post($baseUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => max(40, $maxTokens),
                ]);

            $this->recordRequestMetrics(
                $operation,
                (int) data_get($response->json(), 'usage.total_tokens', 0),
                (bool) $response->ok()
            );

            return $response->json('choices.0.message.content');
        } catch (\Exception $e) {
            $this->recordRequestMetrics($operation, 0, false);
            Log::error('Groq API Error: ' . $e->getMessage());
            return null;
        }
    }

    private function rememberWithMetrics(string $cacheKey, int $ttl, string $operation, callable $resolver): mixed
    {
        if (Cache::has($cacheKey)) {
            $this->recordCacheMetrics($operation, true);
            return Cache::get($cacheKey);
        }

        $this->recordCacheMetrics($operation, false);
        $value = $resolver();
        Cache::put($cacheKey, $value, $ttl);

        return $value;
    }

    private function recordCacheMetrics(string $operation, bool $hit): void
    {
        $day = now()->format('Ymd');
        $type = $hit ? 'cache_hits' : 'cache_misses';
        $this->incrementMetric("ai:metrics:{$day}:{$type}");
        $this->incrementMetric("ai:metrics:{$day}:{$operation}:{$type}");
    }

    private function recordRequestMetrics(string $operation, int $tokens, bool $ok): void
    {
        $day = now()->format('Ymd');
        $minute = now()->format('YmdHi');

        $this->incrementMetric("ai:metrics:{$day}:requests_total");
        $this->incrementMetric("ai:metrics:{$day}:{$operation}:requests_total");
        $this->incrementMetric("ai:metrics:minute:{$minute}:requests_total", 1, 180);

        if ($tokens > 0) {
            $this->incrementMetric("ai:metrics:{$day}:tokens_total", $tokens);
            $this->incrementMetric("ai:metrics:{$day}:{$operation}:tokens_total", $tokens);
            $this->incrementMetric("ai:metrics:minute:{$minute}:tokens_total", $tokens, 180);
        }

        if (! $ok) {
            $this->incrementMetric("ai:metrics:{$day}:errors_total");
            $this->incrementMetric("ai:metrics:{$day}:{$operation}:errors_total");
        }
    }

    private function incrementMetric(string $key, int $by = 1, int $ttlSeconds = 172800): void
    {
        Cache::add($key, 0, $ttlSeconds);
        Cache::increment($key, $by);
    }

    private function extractJsonObject(string $raw): ?string
    {
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        return substr($raw, $start, $end - $start + 1);
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
            'critical_areas' => ['Cálculo Integral'],
            'strengths' => ['Álgebra'],
            'score_projection' => 105,
            'study_plan' => 'Enfocarse en derivaciones e integraciones básicas.',
            'motivational_message' => '¡Vas por excelente camino! Tu constancia te llevará a la UNAM.',
        ];
    }
}