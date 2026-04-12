<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiTutorCache;
use App\Models\Question;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    private const TOKENS_SAVED_PER_CACHE_HIT = 350;

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
}
