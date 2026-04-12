<?php

namespace App\Http\Controllers\Progress;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\SpacedRepetitionCard;
use App\Services\Learning\GamificationService;
use App\Services\Learning\SpacedRepetitionService;
use App\Services\Learning\StudyStreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpacedRepetitionController extends Controller
{
    public function __construct(
        protected SpacedRepetitionService $srs,
        protected StudyStreakService $streakService,
        protected GamificationService $gamification,
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        $dueCards = $this->srs->getDueCards($user);

        return Inertia::render('Progress/Review', [
            'due_cards' => $dueCards,
            'total_due' => $dueCards->count(),
        ]);
    }

    /**
     * Process a single SRS card answer.
     * Called via AJAX from Review.vue and DailyPractice.vue.
     */
    public function answer(Request $request, Question $question): JsonResponse
    {
        $data = $request->validate([
            'quality' => 'required|integer|min:0|max:5',
            'source' => 'required|string|in:review,daily',
        ]);

        $user = auth()->user();

        if (! $this->isQuestionAllowedForSource($request, $user->id, $question->id, $data['source'])) {
            return response()->json([
                'message' => 'La pregunta no pertenece a tu sesion activa.',
            ], 403);
        }

        $correct = $data['quality'] >= 3;

        $this->srs->processAnswer($user, $question, $correct, $data['quality']);
        $this->streakService->recordStudyActivity($user);

        if ($data['source'] === 'daily') {
            $this->consumeDailyQuestionFromSession($request, $question->id);
        }

        $xpAwarded = $this->calculateXp($correct, $data['quality'], $data['source']);
        if ($xpAwarded > 0) {
            $this->gamification->addXP($user, $xpAwarded);
        }

        $totalXp = (int) (($user->fresh()->preferences['xp'] ?? 0));

        return response()->json([
            'status' => 'ok',
            'xp_awarded' => $xpAwarded,
            'total_xp' => $totalXp,
        ]);
    }

    private function isQuestionAllowedForSource(Request $request, int $userId, int $questionId, string $source): bool
    {
        if ($source === 'review') {
            return SpacedRepetitionCard::query()
                ->where('user_id', $userId)
                ->where('question_id', $questionId)
                ->where('next_review_at', '<=', now())
                ->exists();
        }

        $dailyIds = collect($request->session()->get('daily_practice.question_ids', []))
            ->map(fn ($id) => (int) $id)
            ->all();

        // If the session key is missing (e.g. after a deploy/restart), fall back to
        // checking that the question simply exists and is active. This avoids blocking
        // legitimate users who kept the tab open across a deployment.
        if (empty($dailyIds)) {
            return Question::where('id', $questionId)->where('is_active', true)->exists();
        }

        return in_array($questionId, $dailyIds, true);
    }

    private function consumeDailyQuestionFromSession(Request $request, int $questionId): void
    {
        $remaining = collect($request->session()->get('daily_practice.question_ids', []))
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $questionId)
            ->values()
            ->all();

        $request->session()->put('daily_practice.question_ids', $remaining);
    }

    private function calculateXp(bool $correct, int $quality, string $source): int
    {
        if (! $correct) {
            return 1;
        }

        $base = $source === 'daily' ? 10 : 6;
        $bonus = max(0, $quality - 3);

        return $base + $bonus;
    }
}
