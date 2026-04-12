<?php

namespace App\Services\Learning;

use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\GroqService;
use App\Services\RAG\VectorSearchService;

class AdaptiveExamPipelineService
{
    public function __construct(
        protected AdaptiveDifficultyService $difficultyService,
        protected GroqService $groq,
        protected VectorSearchService $vector,
    ) {}

    public function evaluate(
        User $user,
        Subject $subject,
        Question $question,
        int $selectedIndex,
        int $correctStreak,
        ?string $dudaUsuario = null,
        bool $applyAdaptation = true,
    ): array
    {
        $options = (array) ($question->options ?? []);
        $selected = $options[$selectedIndex] ?? '';
        $correct = (string) ($question->correct_answer ?? '');
        $isCorrect = $selected !== '' && $selected === $correct;

        if ($applyAdaptation) {
            $this->difficultyService->adjustDifficulty($user, $subject, $isCorrect);
        }

        $nivel = $isCorrect && $correctStreak >= 3 ? 'subir' : ($isCorrect ? 'mantener' : 'bajar');
        $context = $this->vector->technicalContextForQuestion($question);

        $ai = $this->groq->generateFeedback([
            'pregunta' => (string) $question->stem,
            'correcta' => $correct,
            'usuario' => $selected,
            'resultado' => $isCorrect ? 'ACIERTO' : 'ERROR',
            'contexto_tecnico' => $context,
            'duda_usuario' => $dudaUsuario,
            'datos_examen' => [
                'materia' => (string) ($subject->name ?? ''),
                'tema' => (string) ($question->topic?->name ?? ''),
                'subtema' => (string) ($question->topic?->description ?? ''),
                'dificultad_actual' => (int) ($question->difficulty ?? 0),
            ],
        ]);

        return $this->normalizeOutput($ai, $isCorrect, $nivel, $question, $context, $dudaUsuario);
    }

    private function normalizeOutput(?array $ai, bool $isCorrect, string $nivel, Question $question, string $context, ?string $dudaUsuario): array
    {
        $default = [
            'evaluacion' => [
                'feedback_especifico' => $isCorrect
                    ? 'Resolución correcta. Mantén el mismo enfoque lógico para consolidar el tema.'
                    : 'La selección no coincide con la lógica del reactivo. Revisa el concepto base antes de responder.',
                'semblanza_tema' => $this->buildSemblanza($context),
            ],
            'chat' => [
                'respuesta_directa' => $this->defaultChatReply($dudaUsuario),
                'es_fuera_de_contexto' => false,
            ],
            'metadatos' => [
                'nivel_sugerido' => $nivel,
            ],
        ];

        if (! is_array($ai)) {
            $default['chat']['respuesta_directa'] = 'Servicio IA no disponible temporalmente. Mostrando orientación basada en reglas locales del sistema.';
            return $default;
        }

        $out = array_replace_recursive($default, $ai);

        if (! isset($out['evaluacion']) || ! is_array($out['evaluacion'])) {
            $out['evaluacion'] = [];
        }

        if (isset($out['evaluacion']['semblanza_educativa']) && ! isset($out['evaluacion']['semblanza_tema'])) {
            $out['evaluacion']['semblanza_tema'] = $out['evaluacion']['semblanza_educativa'];
        }

        unset($out['evaluacion']['semblanza_educativa']);

        if (! isset($out['evaluacion']['feedback_especifico']) || ! is_string($out['evaluacion']['feedback_especifico'])) {
            $out['evaluacion']['feedback_especifico'] = $default['evaluacion']['feedback_especifico'];
        }

        if (! isset($out['evaluacion']['semblanza_tema']) || ! is_string($out['evaluacion']['semblanza_tema'])) {
            $out['evaluacion']['semblanza_tema'] = $default['evaluacion']['semblanza_tema'];
        }

        if (! in_array((string) ($out['metadatos']['nivel_sugerido'] ?? ''), ['subir', 'bajar', 'mantener'], true)) {
            $out['metadatos']['nivel_sugerido'] = $nivel;
        }

        $out['chat']['respuesta_directa'] = (string) ($out['chat']['respuesta_directa'] ?? $default['chat']['respuesta_directa']);
        $out['chat']['es_fuera_de_contexto'] = (bool) ($out['chat']['es_fuera_de_contexto'] ?? false);

        return $out;
    }

    private function buildSemblanza(string $context): string
    {
        $text = trim($context);
        if ($text === '') {
            return 'Este tema exige identificar el concepto rector, aplicar su definición formal y verificar consistencia lógica con las opciones.\n\nPara mejorar, compara cada distractor contra el principio teórico central y justifica por qué no satisface la condición del problema.';
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $text) ?: [];
        $first = implode(' ', array_slice($sentences, 0, 2));
        $second = implode(' ', array_slice($sentences, 2, 2));

        $first = trim($first) !== '' ? trim($first) : 'El contexto técnico del tema establece los principios fundamentales para resolver el reactivo.';
        $second = trim($second) !== '' ? trim($second) : 'La estrategia recomendada es aplicar esos principios paso a paso y contrastar cada distractor con la definición formal.';

        return $first . "\n\n" . $second;
    }

    private function defaultChatReply(?string $dudaUsuario): string
    {
        $duda = trim((string) $dudaUsuario);
        if ($duda === '') {
            return 'Si tienes una duda puntual del reactivo, escríbela y te guío sobre el tema actual.';
        }

        return 'Solo puedo asesorarte sobre el tema del examen actual.';
    }
}
