<?php

namespace App\Services;

use App\Models\AiTutorCache;
use App\Models\Question;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    private const STATELESS_TUTOR_SYSTEM_PROMPT = "Eres un tutor académico experto. Resuelve dudas o explica errores basándote ÚNICAMENTE en la 'Respuesta Correcta' y la 'Explicación Oficial' proporcionadas. Escribe máximo 80 palabras. Usa un tono motivador, ve directo al grano (sin saludos largos) y usa negritas para conceptos clave.";

    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres un tutor académico experto y empático, especializado en preparar a estudiantes para exámenes de admisión (COMIPEMS, UNAM, IPN).

Tu objetivo es resolver dudas o explicar errores de manera clara, concisa y motivadora.

REGLAS ESTRICTAS PARA chat.respuesta_directa:
1) BASADO EN DATOS: Basa tu explicación ÚNICAMENTE en "Respuesta Correcta" y "Explicación Oficial" del contexto. No inventes procedimientos alternativos ni alucines información.
2) CONCISIÓN: chat.respuesta_directa no debe superar 80 palabras.
3) PEDAGOGÍA: Si el alumno se equivocó, no lo regañes; explica por qué la opción correcta es correcta y señala sutilmente el posible error del alumno según su respuesta.
4) FORMATO: Usa negritas para conceptos clave. No uses saludos largos ni despedidas.

Si la duda del alumno está fuera del tema actual, marca chat.es_fuera_de_contexto=true y usa exactamente este texto en chat.respuesta_directa:
"Solo puedo asesorarte sobre el tema del examen actual"

Si está dentro del tema, chat.es_fuera_de_contexto=false.

Responde EXCLUSIVAMENTE JSON válido, sin texto adicional, con EXACTAMENTE esta estructura:
{
    "evaluacion": {
        "feedback_especifico": "string",
        "semblanza_tema": "string"
    },
    "chat": {
        "respuesta_directa": "string",
        "es_fuera_de_contexto": false
    },
    "metadatos": {
        "nivel_sugerido": "subir"
    }
}

metadatos.nivel_sugerido solo puede ser "subir", "mantener" o "bajar".
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
                'model' => (string) config('services.groq.model', 'llama-3.1-8b-instant'),
                'temperature' => 0.3,
                'max_tokens' => 300,
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

    public function tutorStateless(array $payload): ?array
    {
        $apiKey = (string) config('services.groq.key');
        if ($apiKey === '') {
            return null;
        }

        $userPrompt = "Analiza la siguiente situación y resuelve la duda basándote en este contexto estricto:\n"
            . "[Contexto]: Materia: " . (string) ($payload['materia'] ?? '')
            . ", Tema: " . (string) ($payload['tema'] ?? '')
            . ", Pregunta: " . (string) ($payload['texto_pregunta'] ?? '')
            . ", Respuesta Correcta: " . (string) ($payload['respuesta_correcta'] ?? '')
            . ", Explicación Oficial: " . (string) ($payload['explicacion_oficial'] ?? '')
            . ".\n"
            . "[Situación del Alumno]: Respondió: " . (string) ($payload['respuesta_alumno'] ?? '')
            . ". Su duda es: " . (string) ($payload['texto_duda'] ?? '')
            . ".\n"
            . "Explica el concepto de forma clara y directa.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post(rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/') . '/chat/completions', [
                'model' => (string) config('services.groq.model', 'llama-3.1-8b-instant'),
                'temperature' => 0.3,
                'max_tokens' => 300,
                'messages' => [
                    ['role' => 'system', 'content' => self::STATELESS_TUTOR_SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

            if (! $response->ok()) {
                Log::warning('Groq Tutor API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $text = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            if ($text === '') {
                return null;
            }

            return [
                'respuesta_directa' => $text,
                'es_fuera_de_contexto' => $text === 'Solo puedo asesorarte sobre el tema del examen actual',
            ];
        } catch (\Throwable $exception) {
            Log::warning('Groq Tutor request failed', ['error' => $exception->getMessage()]);
            return null;
        }
    }

    public function getTutorExplanation(Question $pregunta, string $respuestaAlumno, ?string $dudaUsuario = null): ?array
    {
        $duda = trim((string) $dudaUsuario);
        $canUseCache = $duda === '';

        if ($canUseCache) {
            $cachedResponse = AiTutorCache::query()
                ->where('question_id', $pregunta->id)
                ->where('respuesta_incorrecta', $respuestaAlumno)
                ->first();

            if ($cachedResponse) {
                return [
                    'respuesta_directa' => (string) $cachedResponse->explicacion_ia,
                    'es_fuera_de_contexto' => false,
                ];
            }
        }

        $response = $this->tutorStateless([
            'materia' => (string) ($pregunta->topic?->subject?->name ?? ''),
            'tema' => (string) ($pregunta->topic?->name ?? ''),
            'texto_pregunta' => (string) ($pregunta->stem ?? ''),
            'respuesta_correcta' => (string) ($pregunta->correct_answer ?? ''),
            'explicacion_oficial' => (string) ($pregunta->explanation ?? ''),
            'respuesta_alumno' => $respuestaAlumno,
            'texto_duda' => $duda,
        ]);

        if (
            $canUseCache
            && is_array($response)
            && isset($response['respuesta_directa'])
            && trim((string) $response['respuesta_directa']) !== ''
            && !((bool) ($response['es_fuera_de_contexto'] ?? false))
        ) {
            AiTutorCache::updateOrCreate(
                [
                    'question_id' => $pregunta->id,
                    'respuesta_incorrecta' => $respuestaAlumno,
                ],
                [
                    'explicacion_ia' => (string) $response['respuesta_directa'],
                ]
            );
        }

        return $response;
    }

    private function buildPrompt(array $payload): string
    {
        $datosExamen = $payload['datos_examen'] ?? [];

        $materia = (string) ($datosExamen['materia'] ?? 'N/A');
        $tema = (string) ($datosExamen['tema'] ?? 'N/A');
        $pregunta = (string) ($payload['pregunta'] ?? '');
        $respuestaCorrecta = (string) ($payload['correcta'] ?? '');
        $explicacionOficial = (string) ($payload['explicacion_oficial'] ?? $payload['contexto_tecnico'] ?? '');
        $respuestaAlumno = (string) ($payload['usuario'] ?? '');
        $dudaAlumno = (string) ($payload['duda_usuario'] ?? '');

        return ""
            . "Analiza la siguiente situación del estudiante y resuelve su duda basándote en este contexto estricto:\n\n"
            . "--- CONTEXTO DE LA PREGUNTA ---\n"
            . "Materia: " . $materia . "\n"
            . "Tema: " . $tema . "\n"
            . "Pregunta: \"" . $pregunta . "\"\n"
            . "Respuesta Correcta: \"" . $respuestaCorrecta . "\"\n"
            . "Explicación Oficial: \"" . $explicacionOficial . "\"\n\n"
            . "--- SITUACIÓN DEL ALUMNO ---\n"
            . "Lo que el alumno respondió: \"" . $respuestaAlumno . "\"\n"
            . "Duda o comentario del alumno: \"" . $dudaAlumno . "\"\n\n"
            . "Por favor, explica el concepto y resuelve la duda de forma clara y directa siguiendo tus instrucciones.";
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
