<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiTutorCache;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    private const TOKENS_SAVED_PER_CACHE_HIT = 350;
    private const CURATED_SENTINEL = '__HUMAN_CURATED__';

    public function index(): Response
    {
        $totalCachedExplanations = AiTutorCache::query()->count();

        $bottlenecks = AiTutorCache::query()
            ->selectRaw('question_id, COUNT(*) as error_variants, SUM(COALESCE(hit_count, 1)) as cache_hits')
            ->groupBy('question_id')
            ->orderByDesc('error_variants')
            ->limit(5)
            ->get();

        $questions = Question::query()
            ->whereIn('id', $bottlenecks->pluck('question_id')->all())
            ->get(['id', 'stem'])
            ->keyBy('id');

        $topBottlenecks = $bottlenecks->map(function ($row) use ($questions) {
            $question = $questions->get((int) $row->question_id);

            return [
                'question_id' => (int) $row->question_id,
                'error_variants' => (int) $row->error_variants,
                'cache_hits' => (int) $row->cache_hits,
                'question_preview' => (string) ($question?->stem ?? 'Pregunta no disponible'),
            ];
        })->values();

        $totalCacheHits = (int) AiTutorCache::query()->sum('hit_count');
        if ($totalCacheHits <= 0) {
            $totalCacheHits = $totalCachedExplanations;
        }

        $estimatedTokensSaved = $totalCacheHits * self::TOKENS_SAVED_PER_CACHE_HIT;

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'cached_explanations' => $totalCachedExplanations,
                'critical_questions' => $topBottlenecks->count(),
                'estimated_tokens_saved' => $estimatedTokensSaved,
                'estimated_cache_hits' => $totalCacheHits,
            ],
            'bottlenecks' => $topBottlenecks,
        ]);
    }

    public function curationIndex(): Response
    {
        return Inertia::render('Admin/CurationPanel', [
            'subjects' => Subject::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function searchQuestion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ]);

        $query = trim((string) ($data['query'] ?? ''));
        $subjectId = $data['subject_id'] ?? null;

        $questionQuery = Question::query()->with('topic.subject');

        if ($subjectId) {
            $questionQuery->whereHas('topic', function ($builder) use ($subjectId) {
                $builder->where('subject_id', $subjectId);
            });
        }

        if ($query !== '') {
            if (ctype_digit($query)) {
                $questionQuery->where('id', (int) $query);
            } else {
                $questionQuery->where(function ($builder) use ($query) {
                    $builder->where('stem', 'like', '%' . $query . '%')
                        ->orWhere('correct_answer', 'like', '%' . $query . '%');
                });
            }
        }

        $question = $questionQuery->latest('id')->first();

        if (! $question) {
            return response()->json([
                'message' => 'No se encontró ninguna pregunta con ese criterio.',
            ], 404);
        }

        $cache = AiTutorCache::query()
            ->where('question_id', $question->id)
            ->orderByRaw("CASE WHEN respuesta_incorrecta = ? THEN 0 ELSE 1 END", [self::CURATED_SENTINEL])
            ->orderByDesc('hit_count')
            ->first();

        return response()->json([
            'question' => [
                'id' => (int) $question->id,
                'subject_id' => (int) ($question->topic?->subject?->id ?? 0),
                'subject_name' => (string) ($question->topic?->subject?->name ?? 'N/A'),
                'topic_name' => (string) ($question->topic?->name ?? 'N/A'),
                'stem' => (string) $question->stem,
                'options' => (array) $question->options,
                'correct_answer' => (string) $question->correct_answer,
                'correct_index' => (int) $question->correct_index,
            ],
            'cache' => $cache ? [
                'id' => (int) $cache->id,
                'respuesta_incorrecta' => (string) $cache->respuesta_incorrecta,
                'explicacion_ia' => (string) $cache->explicacion_ia,
                'hit_count' => (int) ($cache->hit_count ?? 0),
            ] : null,
        ]);
    }

    public function updateQuestionAndCache(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'stem' => ['required', 'string', 'min:10'],
            'options' => ['required', 'array', 'size:4'],
            'options.*' => ['required', 'string', 'min:1'],
            'correct_index' => ['required', 'integer', 'min:0', 'max:3'],
            'ai_explanation' => ['required', 'string', 'min:10'],
        ]);

        $question = Question::query()->findOrFail($id);
        $options = array_values(array_map(fn ($opt) => trim((string) $opt), (array) $data['options']));
        $correctIndex = (int) $data['correct_index'];
        $correctAnswer = $options[$correctIndex] ?? '';

        DB::transaction(function () use ($question, $options, $correctAnswer, $data) {
            $question->update([
                'stem' => trim((string) $data['stem']),
                'options' => $options,
                'correct_answer' => $correctAnswer,
            ]);

            // Sobrescribe variantes existentes para alinear la explicación del tutor con curación humana.
            AiTutorCache::query()
                ->where('question_id', $question->id)
                ->update([
                    'explicacion_ia' => trim((string) $data['ai_explanation']),
                    'updated_at' => now(),
                ]);

            AiTutorCache::updateOrCreate(
                [
                    'question_id' => $question->id,
                    'respuesta_incorrecta' => self::CURATED_SENTINEL,
                ],
                [
                    'explicacion_ia' => trim((string) $data['ai_explanation']),
                    'hit_count' => 0,
                ]
            );
        });

        return response()->json([
            'ok' => true,
            'message' => 'Pregunta y explicación IA actualizadas correctamente.',
        ]);
    }
}
