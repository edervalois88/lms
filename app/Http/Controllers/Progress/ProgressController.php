<?php

namespace App\Http\Controllers\Progress;

use App\Http\Controllers\Controller;
use App\Services\Learning\ProgressCalculatorService;
use Inertia\Inertia;
use Inertia\Response;

class ProgressController extends Controller
{
    public function __construct(
        protected ProgressCalculatorService $calculator
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        $weeklyStats = $this->calculator->getWeeklyStats($user);
        $examHistory = $user->exams()
            ->withCount('examAnswers')
            ->latest()
            ->paginate(10);

        return Inertia::render('Progress/Index', [
            'mastery' => $this->calculator->getSubjectMastery($user),
            'projection' => $this->calculator->getScoreProjection($user),
            'streak_days' => (int) ($user->streak_days ?? 0),
            'exams_history' => $examHistory->items(),
            'exams_pagination' => [
                'current_page' => $examHistory->currentPage(),
                'last_page' => $examHistory->lastPage(),
                'per_page' => $examHistory->perPage(),
                'total' => $examHistory->total(),
            ],
            'weekly_stats' => $weeklyStats,
        ]);
    }
}
