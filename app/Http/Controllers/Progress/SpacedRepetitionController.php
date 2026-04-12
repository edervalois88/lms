<?php

namespace App\Http\Controllers\Progress;

use App\Http\Controllers\Controller;
use App\Models\Question;
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
        ]);

        $user = auth()->user();
        $correct = $data['quality'] >= 3;

        $this->srs->processAnswer($user, $question, $correct, $data['quality']);
        $this->streakService->recordStudyActivity($user);

        return response()->json(['status' => 'ok']);
    }
}
