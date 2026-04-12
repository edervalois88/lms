<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PopulateComipemsDb extends Command
{
    protected $signature = 'db:populate-comipems {materia} {cantidad_lotes=1}';

    protected $description = 'Genera preguntas nivel COMIPEMS usando Groq IA y las guarda en la BD';

    public function handle(): int
    {
        $materiaNombre = trim((string) $this->argument('materia'));
        $lotes = max(1, (int) $this->argument('cantidad_lotes'));

        $subject = $this->resolveSubject($materiaNombre);
        if (! $subject) {
            $this->error("No se encontro la materia '{$materiaNombre}' en la tabla subjects.");
            return self::FAILURE;
        }

        $topic = $this->resolveTopic($subject);

        $this->info("Iniciando generacion para: {$subject->name} (topic: {$topic->name})");

        $inserted = 0;
        $skipped = 0;

        for ($i = 1; $i <= $lotes; $i++) {
            $this->warn("Generando lote {$i} de {$lotes}...");
            $preguntas = $this->llamarGroqGenerador($subject->name);

            if (empty($preguntas)) {
                $this->error("Fallo al generar el lote {$i}. Saltando...");
                continue;
            }

            $batchCount = 0;

            foreach ($preguntas as $p) {
                $normalized = $this->normalizeQuestion($p);
                if (! $normalized) {
                    continue;
                }

                $alreadyExists = Question::query()
                    ->where('topic_id', $topic->id)
                    ->where('stem', $normalized['pregunta'])
                    ->exists();

                if ($alreadyExists) {
                    $skipped++;
                    continue;
                }

                Question::create([
                    'topic_id' => $topic->id,
                    'created_by' => null,
                    'type' => 'multiple_choice',
                    'stem' => $normalized['pregunta'],
                    'options' => [
                        $normalized['A'],
                        $normalized['B'],
                        $normalized['C'],
                        $normalized['D'],
                    ],
                    // En este esquema se guarda el texto de la opcion correcta, no la letra.
                    'correct_answer' => $normalized[$normalized['opcion_correcta']],
                    'explanation' => $normalized['explicacion'],
                    'difficulty' => random_int(2, 6),
                    'is_ai_generated' => true,
                    'is_active' => true,
                ]);

                $batchCount++;
                $inserted++;
            }

            $this->info("Lote {$i} guardado. Insertadas: {$batchCount} | Duplicadas omitidas: {$skipped}");
            sleep(2);
        }

        $this->info("Poblacion completada. Total insertado: {$inserted} | Total omitido por duplicado: {$skipped}");
        return self::SUCCESS;
    }

    private function llamarGroqGenerador(string $materia): ?array
    {
        $apiKey = (string) config('services.groq.key');
        $model = (string) config('services.groq.model', 'llama-3.1-8b-instant');
        $baseUrl = rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');

        if ($apiKey === '') {
            $this->error('GROQ_API_KEY no esta configurada.');
            return null;
        }

        $prompt = "Eres un creador de examenes experto. Genera exactamente 10 preguntas de opcion multiple nivel COMIPEMS sobre '{$materia}'. "
            . "Devuelve SOLO JSON valido con esta estructura exacta: "
            . "{\"preguntas\":[{\"pregunta\":\"...\",\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\",\"opcion_correcta\":\"A\",\"explicacion\":\"...\"}]}. "
            . "Reglas: sin markdown, sin texto extra, sin claves adicionales.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($baseUrl . '/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.4,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (! $response->successful()) {
            $this->error('Groq respondio con error: ' . $response->status());
            return null;
        }

        $raw = (string) data_get($response->json(), 'choices.0.message.content', '');
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded['preguntas']) && is_array($decoded['preguntas'])) {
            return $decoded['preguntas'];
        }

        foreach ($decoded as $value) {
            if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                return $value;
            }
        }

        return null;
    }

    private function normalizeQuestion(array $p): ?array
    {
        $required = ['pregunta', 'A', 'B', 'C', 'D', 'opcion_correcta', 'explicacion'];
        foreach ($required as $field) {
            if (! isset($p[$field]) || trim((string) $p[$field]) === '') {
                return null;
            }
        }

        $letter = strtoupper(trim((string) $p['opcion_correcta']));
        if (! in_array($letter, ['A', 'B', 'C', 'D'], true)) {
            return null;
        }

        return [
            'pregunta' => trim((string) $p['pregunta']),
            'A' => trim((string) $p['A']),
            'B' => trim((string) $p['B']),
            'C' => trim((string) $p['C']),
            'D' => trim((string) $p['D']),
            'opcion_correcta' => $letter,
            'explicacion' => trim((string) $p['explicacion']),
        ];
    }

    private function resolveSubject(string $materiaNombre): ?Subject
    {
        $exact = Subject::query()->whereRaw('LOWER(name) = ?', [Str::lower($materiaNombre)])->first();
        if ($exact) {
            return $exact;
        }

        return Subject::query()
            ->where('name', 'like', '%' . $materiaNombre . '%')
            ->first();
    }

    private function resolveTopic(Subject $subject): Topic
    {
        $existing = $subject->topics()->inRandomOrder()->first();
        if ($existing) {
            return $existing;
        }

        return Topic::create([
            'subject_id' => $subject->id,
            'parent_id' => null,
            'name' => 'Banco IA ' . $subject->name,
            'slug' => Str::slug('banco-ia-' . $subject->name . '-' . now()->timestamp),
            'description' => 'Topico autogenerado para poblar reactivos COMIPEMS con IA.',
            'difficulty_base' => 3,
            'sort_order' => 999,
        ]);
    }
}
