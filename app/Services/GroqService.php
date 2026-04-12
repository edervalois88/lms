<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Actúa como Tutor Académico Adaptativo.
Tu única fuente de información veraz es CONTEXTO_TECNICO y los datos del reactivo.

Objetivo:
1) Evaluar por qué la respuesta del estudiante fue correcta o incorrecta con precisión.
2) Entregar feedback pedagógico breve, accionable y sin ambiguedad.
3) Responder dudas del estudiante solo dentro del contexto del tema actual.

Reglas críticas:
- Si la DUDA_USUARIO está fuera del tema del reactivo, marca es_fuera_de_contexto=true y responde exactamente: "Solo puedo asesorarte sobre el tema del examen actual".
- Si está dentro de contexto, es_fuera_de_contexto=false y ofrece una respuesta clara y concreta.
- No inventes teoría fuera de CONTEXTO_TECNICO.
- No uses Markdown, ni texto fuera del JSON.

Responde exclusivamente en JSON estricto con EXACTAMENTE esta estructura:
{
    "evaluacion": { "feedback_especifico": "string", "semblanza_tema": "string" },
  "chat": { "respuesta_directa": "string", "es_fuera_de_contexto": "boolean" },
  "metadatos": { "nivel_sugerido": "string" }
}

Donde metadatos.nivel_sugerido solo puede ser: "subir", "mantener" o "bajar".
PROMPT;

    public function generateFeedback(array $payload): ?array
    {
        $apiKey = (string) config('services.groq.key');
        if ($apiKey === '') {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post(rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/') . '/chat/completions', [
                'model' => (string) config('services.groq.model', 'llama-3.3-70b-versatile'),
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => $this->buildPrompt($payload)],
                ],
            ]);

            if (! $response->ok()) {
                Log::warning('Groq API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $content = (string) data_get($response->json(), 'choices.0.message.content', '');
            $json = $this->extractJson($content);
            return $json !== null ? $this->normalizeSchema($json) : null;
        } catch (\Throwable $exception) {
            Log::warning('Groq request failed', ['error' => $exception->getMessage()]);
            return null;
        }
    }

    private function buildPrompt(array $payload): string
    {
        $datosExamen = $payload['datos_examen'] ?? [];
        $materia = (string) ($datosExamen['materia'] ?? 'N/A');
        $tema = (string) ($datosExamen['tema'] ?? 'N/A');
        $subtema = (string) ($datosExamen['subtema'] ?? 'N/A');
        $dificultad = (string) ($datosExamen['dificultad_actual'] ?? 'N/A');

        return ""
            . "DATOS_EXAMEN:\n"
            . "- Materia: " . $materia . "\n"
            . "- Tema: " . $tema . "\n"
            . "- Subtema: " . $subtema . "\n"
            . "- Dificultad actual: " . $dificultad . "\n"
            . "Pregunta: " . (string) ($payload['pregunta'] ?? '') . "\n"
            . "Respuesta Correcta: " . (string) ($payload['correcta'] ?? '') . "\n"
            . "Respuesta Usuario: " . (string) ($payload['usuario'] ?? '') . "\n"
            . "Resultado: " . (string) ($payload['resultado'] ?? 'ERROR') . "\n"
            . "DUDA_USUARIO: " . (string) ($payload['duda_usuario'] ?? 'N/A') . "\n"
            . "CONTEXTO_TECNICO:\n" . (string) ($payload['contexto_tecnico'] ?? 'Sin contexto técnico') . "\n"
            . "Regla de enfoque: si la duda está fuera del tema actual, marca es_fuera_de_contexto=true y responde solo: Solo puedo asesorarte sobre el tema del examen actual";
    }

    private function extractJson(string $content): ?array
    {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $matches) !== 1) {
            return null;
        }

        $decoded = json_decode($matches[0], true);
        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeSchema(array $data): array
    {
        if (! isset($data['evaluacion']) || ! is_array($data['evaluacion'])) {
            $data['evaluacion'] = [];
        }

        if (isset($data['evaluacion']['semblanza_educativa']) && ! isset($data['evaluacion']['semblanza_tema'])) {
            $data['evaluacion']['semblanza_tema'] = $data['evaluacion']['semblanza_educativa'];
        }

        unset($data['evaluacion']['semblanza_educativa']);

        return $data;
    }
}
